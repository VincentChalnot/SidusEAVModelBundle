<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use UnexpectedValueException;

class AttributeConfigurationHandler
{
    /** @var AttributeInterface[] */
    protected $attributes;

    /**
     * @param AttributeInterface $attribute
     */
    public function addAttribute(AttributeInterface $attribute)
    {
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
     * @param $code
     * @return AttributeInterface
     * @throws UnexpectedValueException
     */
    public function getAttribute($code)
    {
        if (empty($this->attributes[$code])) {
            throw new UnexpectedValueException("No attribute with code : {$code}");
        }
        return $this->attributes[$code];
    }
}