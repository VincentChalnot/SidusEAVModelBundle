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

namespace Sidus\EAVModelBundle\Family;

use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Data\ValueInterface;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;

/**
 * Interface for EAV families.
 *
 * A family defines a type of data in the EAV model. It specifies which
 * attributes a data entity can have and how it should be created.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface FamilyInterface
{
    /**
     * Returns the unique code identifying this family.
     */
    public function getCode(): string;

    /**
     * Returns the human-readable label for this family.
     */
    public function getLabel(): string;

    /**
     * Returns the type identifier for polymorphism/inheritance.
     */
    public function getType(): ?string;

    /**
     * Returns the class used to create data entities for this family.
     *
     * @return class-string<DataInterface>
     */
    public function getDataClass(): string;

    /**
     * Returns the class used to create value entities for this family.
     *
     * @return class-string<ValueInterface>
     */
    public function getValueClass(): string;

    /**
     * Returns the parent family, if this family inherits from another.
     */
    public function getParent(): ?FamilyInterface;

    /**
     * Returns all child families that inherit from this family.
     *
     * @return array<string, FamilyInterface>
     */
    public function getChildren(): array;

    /**
     * Returns all attributes defined for this family.
     *
     * @return array<string, AttributeInterface>
     */
    public function getAttributes(): array;

    /**
     * Returns an attribute by its code.
     *
     * @throws MissingAttributeException
     */
    public function getAttribute(string $code): AttributeInterface;

    /**
     * Checks if this family has an attribute with the given code.
     */
    public function hasAttribute(string $code): bool;

    /**
     * Returns the attribute used as the label for data entities.
     */
    public function getAttributeAsLabel(): ?AttributeInterface;

    /**
     * Returns the attribute used as the business identifier.
     */
    public function getAttributeAsIdentifier(): ?AttributeInterface;

    /**
     * Whether this family can be instantiated directly.
     */
    public function isInstantiable(): bool;

    /**
     * Whether this family is a singleton (only one instance allowed).
     */
    public function isSingleton(): bool;

    /**
     * Returns custom options for this family.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Returns a specific option value, with an optional default.
     */
    public function getOption(string $code, mixed $default = null): mixed;

    /**
     * Whether this family has a specific option.
     */
    public function hasOption(string $code): bool;

    /**
     * Returns the current context for this family.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Creates a new data entity for this family.
     */
    public function createData(): DataInterface;

    /**
     * Creates a new value entity for an attribute.
     */
    public function createValue(DataInterface $data, AttributeInterface $attribute): ValueInterface;

    /**
     * Returns attribute codes that match a pattern.
     *
     * @return string[]
     */
    public function getMatchingCodes(string $pattern): array;
}
