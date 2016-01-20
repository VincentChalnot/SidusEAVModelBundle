<?php

namespace Sidus\EAVModelBundle\Configuration;

use Sidus\EAVModelBundle\Model\AttributeTypeInterface;
use UnexpectedValueException;

class AttributeTypeConfigurationHandler
{
    /** @var AttributeTypeInterface[] */
    protected $types;

    /**
     * @param AttributeTypeInterface $type
     */
    public function addType(AttributeTypeInterface $type)
    {
        $this->types[$type->getCode()] = $type;
    }

    /**
     * @return AttributeTypeInterface[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param $code
     * @return AttributeTypeInterface
     * @throws UnexpectedValueException
     */
    public function getType($code)
    {
        if (empty($this->types[$code])) {
            throw new UnexpectedValueException("No type with code : {$code}");
        }
        return $this->types[$code];
    }
}