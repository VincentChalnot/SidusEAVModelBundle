<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Form;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Helper to append an attribute to a form type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
