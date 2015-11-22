<?php

namespace Sidus\EAVModelBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

interface FilterInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return FormTypeInterface|string
     */
    public function getFormType();

    public function handleForm(FormInterface $form, QueryBuilder $qb, $alias);
}
