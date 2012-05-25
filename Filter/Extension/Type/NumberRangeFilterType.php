<?php

namespace Lexik\Bundle\FormFilterBundle\Filter\Extension\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Lexik\Bundle\FormFilterBundle\Filter\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Filter type for numbers.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class NumberRangeFilterType extends AbstractType implements FilterTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('left_number', 'filter_number', $options['left_number']);
        $builder->add('right_number', 'filter_number', $options['right_number']);

        $builder->setAttribute('filter_value_keys', array(
                'left_number' => $options['left_number'],
                'right_number' => $options['right_number']));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'left_number' => array('condition_operator' => NumberFilterType::OPERATOR_GREATER_THAN_EQUAL),
            'right_number' => array('condition_operator' => NumberFilterType::OPERATOR_LOWER_THAN_EQUAL),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'filter';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filter_number_range';
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformerId()
    {
        return 'lexik_form_filter.transformer.value_keys';
    }

    /**
     * {@inheritdoc}
     */
    public function applyFilter(QueryBuilder $queryBuilder, Expr $e, $field, $values)
    {
        $value = $values['value'];
        if (isset($value['left_number'][0], $value['right_number'][0])) {
            $leftCond   = $value['left_number']['condition_operator'];
            $leftValue  = $value['left_number'][0];
            $rightCond  = $value['right_number']['condition_operator'];
            $rightValue = $value['right_number'][0];
            $queryBuilder->andWhere($e->andX($e->$leftCond($field, $leftValue), $e->$rightCond($field, $rightValue)));
        }
    }
}
