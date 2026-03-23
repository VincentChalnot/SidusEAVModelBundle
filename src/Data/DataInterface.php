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

namespace Sidus\EAVModelBundle\Data;

use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Exception\InvalidValueDataException;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Family\FamilyInterface;

/**
 * Interface for EAV data entities.
 *
 * Data entities represent instances of families. They store their attribute
 * values through associated Value entities.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DataInterface
{
    /**
     * Returns the primary identifier (usually the database ID).
     */
    public function getId(): int|string|null;

    /**
     * Returns the business identifier (ID or custom identifier attribute).
     *
     * @throws InvalidValueDataException
     */
    public function getIdentifier(): int|string|null;

    /**
     * Returns the family code.
     */
    public function getFamilyCode(): string;

    /**
     * Returns the family this data belongs to.
     */
    public function getFamily(): FamilyInterface;

    /**
     * Returns the parent data, if any.
     */
    public function getParent(): ?DataInterface;

    /**
     * Sets the parent data.
     */
    public function setParent(?DataInterface $parent): static;

    /**
     * Returns the human-readable label for this data.
     */
    public function getLabel(): string;

    /**
     * Gets an attribute value by its code.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function get(string $attributeCode, ?array $context = null): mixed;

    /**
     * Sets an attribute value by its code.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function set(string $attributeCode, mixed $value, ?array $context = null): static;

    /**
     * Appends a value to a collection attribute.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     * @throws \LogicException If attribute is not a collection
     */
    public function add(string $attributeCode, mixed $value, ?array $context = null): static;

    /**
     * Removes a value from a collection attribute.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     * @throws \LogicException If attribute is not a collection
     */
    public function remove(string $attributeCode, mixed $value, ?array $context = null): static;

    /**
     * Returns all values for an attribute.
     *
     * @param array<string, mixed>|null $context
     * @return iterable<ValueInterface>
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function getValues(?AttributeInterface $attribute = null, ?array $context = null): iterable;

    /**
     * Returns the first value for an attribute.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function getValue(AttributeInterface $attribute, ?array $context = null): ?ValueInterface;

    /**
     * Gets the raw data of a value for an attribute.
     *
     * @internal Use get() for normal access
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function getValueData(AttributeInterface $attribute, ?array $context = null): mixed;

    /**
     * Gets the raw data of all values for a collection attribute.
     *
     * @internal Use get() for normal access
     * @param array<string, mixed>|null $context
     * @return iterable<mixed>
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function getValuesData(AttributeInterface $attribute, ?array $context = null): iterable;

    /**
     * Sets the raw data of a value for an attribute.
     *
     * @internal Use set() for normal access
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function setValueData(AttributeInterface $attribute, mixed $dataValue, ?array $context = null): static;

    /**
     * Sets the raw data of all values for a collection attribute.
     *
     * @internal Use set() for normal access
     * @param iterable<mixed> $dataValues
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function setValuesData(AttributeInterface $attribute, iterable $dataValues, ?array $context = null): static;

    /**
     * Removes all values for an attribute.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function emptyValues(?AttributeInterface $attribute = null, ?array $context = null): static;

    /**
     * Adds a value to the internal values collection.
     *
     * @throws ContextException
     */
    public function addValue(ValueInterface $value): static;

    /**
     * Appends data to an attribute's values.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function addValueData(AttributeInterface $attribute, mixed $valueData, ?array $context = null): static;

    /**
     * Removes a value from the internal values collection.
     */
    public function removeValue(ValueInterface $value): static;

    /**
     * Returns all values that reference this data.
     *
     * @param array<string, mixed>|null $context
     * @return iterable<ValueInterface>
     * @throws ContextException
     */
    public function getRefererValues(
        ?FamilyInterface $family = null,
        ?AttributeInterface $attribute = null,
        ?array $context = null
    ): iterable;

    /**
     * Returns all data that references this data.
     *
     * @param array<string, mixed>|null $context
     * @return iterable<DataInterface>
     * @throws ContextException
     */
    public function getRefererDatas(
        ?FamilyInterface $family = null,
        ?AttributeInterface $attribute = null,
        ?array $context = null
    ): iterable;

    /**
     * Creates a value for an attribute.
     *
     * @internal
     * @param array<string, mixed>|null $context
     * @throws MissingAttributeException
     */
    public function createValue(AttributeInterface $attribute, ?array $context = null): ValueInterface;

    /**
     * Checks if an attribute has any values.
     *
     * @param array<string, mixed>|null $context
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     */
    public function isEmpty(AttributeInterface $attribute, ?array $context = null): bool;

    /**
     * String representation.
     */
    public function __toString(): string;
}
