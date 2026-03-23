<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Attribute;

use Sidus\EAVModelBundle\Exception\MissingAttributeTypeException;

/**
 * Registry for attribute types.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeTypeRegistry
{
    /** @var array<string, AttributeTypeInterface> */
    private array $types = [];

    /**
     * Registers an attribute type in the registry.
     */
    public function addType(AttributeTypeInterface $type): void
    {
        $this->types[$type->getCode()] = $type;
    }

    /**
     * Returns an attribute type by its code.
     *
     * @throws MissingAttributeTypeException
     */
    public function getType(string $code): AttributeTypeInterface
    {
        if (!isset($this->types[$code])) {
            throw MissingAttributeTypeException::create($code);
        }

        return $this->types[$code];
    }

    /**
     * Checks if an attribute type exists.
     */
    public function hasType(string $code): bool
    {
        return isset($this->types[$code]);
    }

    /**
     * Returns all registered attribute types.
     *
     * @return array<string, AttributeTypeInterface>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Returns all attribute type codes.
     *
     * @return string[]
     */
    public function getTypeCodes(): array
    {
        return array_keys($this->types);
    }
}
