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

use Sidus\EAVModelBundle\Family\FamilyInterface;

/**
 * Interface for EAV attributes.
 *
 * Attributes define the properties of a family. They describe what data
 * can be stored and how it should be handled.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AttributeInterface
{
    /**
     * Returns the unique code identifying this attribute.
     */
    public function getCode(): string;

    /**
     * Returns the attribute type.
     */
    public function getType(): AttributeTypeInterface;

    /**
     * Returns the family this attribute belongs to.
     */
    public function getFamily(): ?FamilyInterface;

    /**
     * Sets the family this attribute belongs to.
     */
    public function setFamily(FamilyInterface $family): void;

    /**
     * Returns the human-readable label for this attribute.
     */
    public function getLabel(): string;

    /**
     * Returns the group this attribute belongs to, if any.
     */
    public function getGroup(): ?string;

    /**
     * Returns custom options for this attribute.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Returns a specific option value, with an optional default.
     */
    public function getOption(string $code, mixed $default = null): mixed;

    /**
     * Whether this attribute has a specific option.
     */
    public function hasOption(string $code): bool;

    /**
     * Whether this attribute is required.
     */
    public function isRequired(): bool;

    /**
     * Whether this attribute must be unique within the family.
     */
    public function isUnique(): bool;

    /**
     * Whether this attribute can hold multiple values.
     */
    public function isCollection(): bool;

    /**
     * Returns the validation rules for this attribute.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValidationRules(): array;

    /**
     * Returns the default value for this attribute.
     */
    public function getDefault(): mixed;

    /**
     * Returns the context mask for this attribute.
     *
     * The context mask defines which context keys affect this attribute's values.
     *
     * @return string[]
     */
    public function getContextMask(): array;

    /**
     * Checks if a context matches this attribute's context mask.
     *
     * @param array<string, mixed> $context
     */
    public function isContextMatching(array $context): bool;

    /**
     * Merges configuration from another attribute into this one.
     *
     * @param array<string, mixed> $configuration
     */
    public function mergeConfiguration(array $configuration): void;
}
