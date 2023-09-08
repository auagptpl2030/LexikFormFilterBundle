<?php

namespace Lexik\Bundle\FormFilterBundle\Tests\Event\Subscriber;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Lexik\Bundle\FormFilterBundle\Event\Subscriber\DoctrineORMSubscriber;
use Lexik\Bundle\FormFilterBundle\Filter\Doctrine\ORMQuery;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Item;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Options;
use Lexik\Bundle\FormFilterBundle\Tests\Fixtures\Entity\Tag;
use Lexik\Bundle\FormFilterBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DoctrineORMSubscriberTest extends TestCase
{
    /** @var EntityManager  */
    private $em;
    /** @var SchemaTool */
    private $st;
    /** @var Item */
    private $item;
    /** @var ORMQuery|MockObject */
    private $queryMock;

    public function setUp()
    {
        parent::setUp();
        $this->queryMock = $this->getQueryMock();
        $this->em = $this->getSqliteEntityManager();
        $this->st = new SchemaTool($this->em);
        $this->st->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->em->persist($this->getItem());
        $this->em->flush();

        $this->item = $this->em->find(Item::class, 1);
    }

    protected function tearDown()
    {
        $this->st->dropDatabase();
        parent::tearDown();
    }

    public function testFilterEntityForAssociationWithIntegerTypePK()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->from(Item::class, 'i')
            ->join('i.options', 'o')
            ->where('o.id = :id')
            ->setParameter('id', 1);

        $event = $this->getEventMock(
            $this->queryMock,
            $this->em->createQueryBuilder(),
            $this->item->getOptions()->first(),
            'i',
            'i.options'
        );

        $event->expects($this->once())
            ->method('setCondition')
            ->willReturnCallback(function ($expression, array $parameters) use ($event) {
                $this->assertSame(
                    array(
                        $this->item->getOptions()->first()->getId(),
                        Types::INTEGER
                    ),
                    reset($parameters)
                );
            });

        $subscriber = new DoctrineORMSubscriber();
        $subscriber->filterEntity($event);
    }

    public function testFilterEntityForAssociationWithGUIDTypePK()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->from(Item::class, 'i')
            ->join('i.tags', 't')
            ->where('t.id = :id')
            ->setParameter('id', '4cf74f79-00fc-4ded-965e-773e7c5d3bdd');

        $event = $this->getEventMock(
            $this->queryMock,
            $qb,
            $this->item->getTags()->first(),
            'i',
            'i.tags'
        );

        $event->expects($this->once())
            ->method('setCondition')
            ->willReturnCallback(function ($expression, array $parameters) use ($event) {
                $this->assertSame(
                    array(
                        $this->item->getTags()->first()->getId(),
                        Types::GUID
                    ),
                    reset($parameters)
                );
            });

        $subscriber = new DoctrineORMSubscriber();
        $subscriber->filterEntity($event);
    }

    private function getQueryMock()
    {
        $query = $this->getMockBuilder(ORMQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects($this->any())
            ->method('getExpr')
            ->willReturn(new Expr());

        return $query;
    }

    private function getEventMock(ORMQuery $query, QueryBuilder $queryBuilder, $value, $alias, $joinField)
    {
        $event = $this->getMockBuilder(GetFilterConditionEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getFilterQuery')
            ->willReturn($query);
        $event->expects($this->any())
            ->method('getValues')
            ->willReturn([
                'value' => $value,
                'alias' => $alias
            ]);
        $event->expects($this->any())
            ->method('getField')
            ->willReturn($joinField);
        $event->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        return $event;
    }

    private function getItem()
    {
        $options = new Options();
        $options->setLabel('test label');
        $options->setRank(1);

        $tag = new Tag();
        $tag->setName('test tag name');

        $item = new Item();
        $item->setName('test item name');
        $item->setPosition(1);
        $item->setEnabled(true);
        $item->getOptions()->add($options);
        $item->getTags()->add($tag);

        return $item;
    }
}
