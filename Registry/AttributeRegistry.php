<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\Registry;

use Sidus\EAVModelBundle\Exception\AttributeConfigurationException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Translation\TranslatorInterface;
use UnexpectedValueException;

/**
 * Container for all global attributes.
 *
 * @author   Vincent Chalnot <vincent@sidus.fr>
 *
 * @internal Don't use this service to fetch attributes, use the families instead
 */
class AttributeRegistry
{
    /** @var string */
    protected $attributeClass;

    /** @var array */
    protected $globalContextMask;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var AttributeInterface[] */
    protected $attributes = [];

    /** @var array */
    protected static $reservedCodes = [
        'id',
        'identifier',
        'parent',
        'children',
        'values',
        'value',
        'valueData',
        'valuesData',
        'refererValues',
        'createdAt',
        'updatedAt',
        'family',
        'familyCode',
        'currentContext',
        'empty',
    ];

    /**
     * @param string $attributeClass
     * @param array $globalContextMask
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        $attributeClass,
        array $globalContextMask,
        AttributeTypeRegistry $attributeTypeRegistry,
        TranslatorInterface $translator
    ) {
        $this->attributeClass = $attributeClass;
        $this->globalContextMask = $globalContextMask;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->translator = $translator;
    }

    /**
     * @param array $globalConfig
     *
     * @throws \UnexpectedValueException
     */
    public function parseGlobalConfig(array $globalConfig)
    {
        foreach ($globalConfig as $code => $configuration) {
            $attribute = $this->createAttribute($code, $configuration);
            $this->addAttribute($attribute);
        }
    }

    /**
     * @param string $code
     * @param array  $attributeConfiguration
     *
     * @throws \UnexpectedValueException
     *
     * @return AttributeInterface
     */
    public function createAttribute($code, array $attributeConfiguration = [])
    {
        $attributeClass = $this->attributeClass;
        /** @var AttributeInterface $attribute */
        $attribute = new $attributeClass(
            $code,
            $this->attributeTypeRegistry,
            $attributeConfiguration,
            $this->globalContextMask
        );
        if (method_exists($attribute, 'setTranslator')) {
            $attribute->setTranslator($this->translator);
        }

        return $attribute;
    }

    /**
     * @return AttributeInterface[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return AttributeInterface
     */
    public function getAttribute($code)
    {
        if (!$this->hasAttribute($code)) {
            throw new UnexpectedValueException("No attribute with code : {$code}");
        }

        return $this->attributes[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasAttribute($code)
    {
        return array_key_exists($code, $this->attributes);
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @throws \UnexpectedValueException
     */
    protected function addAttribute(AttributeInterface $attribute)
    {
        if (in_array($attribute->getCode(), static::$reservedCodes, true)) {
            throw new AttributeConfigurationException("Attribute code '{$attribute->getCode()}' is a reserved code");
        }
        $this->attributes[$attribute->getCode()] = $attribute;
    }
}
