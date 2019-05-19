<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @throws UnexpectedValueException
     *
     * @return AttributeTypeInterface
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
