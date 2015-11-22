<?php

namespace Sidus\EAVModelBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

class AttributeFilter implements FilterInterface
{
    /** @var string */
    protected $name;

    /** @var AttributeInterface */
    protected $attribute;

    /** @var FormTypeInterface|string */
    protected $formType;

    /**
     * @param $name
     * @param AttributeInterface $attribute
     * @param FormTypeInterface $formType
     */
    public function __construct($name, AttributeInterface $attribute, $formType)
    {
        $this->name = $name;
        $this->attribute = $attribute;
        $this->formType = $formType;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return AttributeInterface
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return FormTypeInterface|string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    public function handleForm(FormInterface $form, QueryBuilder $qb, $alias)
    {
        $data = $form->getData();
        if (!$data) {
            return;
        }
        $uid = uniqid();
        $qb
            ->andWhere("{$alias}.attributeCode = :attribute{$uid}")
            ->setParameter('attribute' . $uid, $this->attribute->getCode())
        ;
        $this->addValueCondition($qb, $alias, $data);
    }

    protected function addValueCondition(QueryBuilder $qb, $alias, $data)
    {
        $uid = uniqid();
        $databaseType = $this->attribute->getType()->getDatabaseType();
        $qb
            ->andWhere("{$alias}.{$databaseType} LIKE :filter{$uid}")
            ->setParameter('filter' . $uid, $data . '%')
        ;
    }
}
