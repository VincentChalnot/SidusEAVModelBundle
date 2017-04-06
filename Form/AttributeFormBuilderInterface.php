<?php

namespace Sidus\EAVModelBundle\Form;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Helper to append an attribute to a form type
 */
interface AttributeFormBuilderInterface
{
    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $options
     *
     * @throws \Exception
     */
    public function addAttribute(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $options = []
    );
}
