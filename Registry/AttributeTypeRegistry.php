<?php

namespace Sidus\EAVModelBundle\Registry;

use Sidus\EAVModelBundle\Exception\MissingAttributeTypeException;
use Sidus\EAVModelBundle\Model\AttributeTypeInterface;
use UnexpectedValueException;

/**
 * Container for all attribute types
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeTypeRegistry
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
     * @param string $code
     *
     * @return AttributeTypeInterface
     * @throws UnexpectedValueException
     */
    public function getType($code)
    {
        if (!$this->hasType($code)) {
            throw new MissingAttributeTypeException($code);
        }

        return $this->types[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasType($code)
    {
        return array_key_exists($code, $this->types);
    }
}
