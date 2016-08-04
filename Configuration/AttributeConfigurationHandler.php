<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use UnexpectedValueException;

/**
 * Container for all attributes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeConfigurationHandler
{
    /** @var AttributeInterface[] */
    protected $attributes;

    protected static $reservedCodes = [
        'id',
        'parent',
        'children',
        'values',
        'valueData',
        'createdAt',
        'updatedAt',
        'family',
        'currentContext',
        'identifier',
        'stringIdentifier',
        'integerIdentifier',
    ];

    /**
     * @param AttributeInterface $attribute
     *
     * @throws \UnexpectedValueException
     */
    public function addAttribute(AttributeInterface $attribute)
    {
        if (in_array($attribute->getCode(), static::$reservedCodes, true)) {
            throw new UnexpectedValueException("Attribute code '{$attribute->getCode()}' is a reserved code");
        }
        $this->attributes[$attribute->getCode()] = $attribute;
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
        if (empty($this->attributes[$code])) {
            throw new UnexpectedValueException("No attribute with code : {$code}");
        }

        return $this->attributes[$code];
    }
}
