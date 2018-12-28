<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\BaseBundle\Request\ParamConverter\AbstractParamConverter;
use Sidus\EAVModelBundle\Registry\AttributeRegistry;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Convert request parameters in attributes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @deprecated Do not access attribute directly without a family
 */
class AttributeParamConverter extends AbstractParamConverter
{
    /** @var AttributeRegistry */
    protected $attributeRegistry;

    /**
     * @param AttributeRegistry $attributeRegistry
     */
    public function __construct(AttributeRegistry $attributeRegistry)
    {
        $this->attributeRegistry = $attributeRegistry;
    }

    /**
     * @param string         $value
     * @param ParamConverter $configuration
     *
     * @throws \UnexpectedValueException
     *
     * @return AttributeInterface
     */
    protected function convertValue($value, ParamConverter $configuration)
    {
        return $this->attributeRegistry->getAttribute($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return AttributeInterface::class;
    }
}
