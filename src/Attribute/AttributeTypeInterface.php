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

/**
 * Interface for attribute type services.
 *
 * Attribute types define how an attribute behaves and where its data is stored.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AttributeTypeInterface
{
    /**
     * Returns the unique code identifying this attribute type.
     */
    public function getCode(): string;

    /**
     * Returns the database type used to store this attribute's values.
     *
     * This corresponds to the column that will be used in the value entity.
     * Common values include: 'stringValue', 'textValue', 'integerValue',
     * 'decimalValue', 'boolValue', 'dateValue', 'datetimeValue', 'dataValue'.
     */
    public function getDatabaseType(): string;

    /**
     * Whether this type represents an embedded (owned) relation.
     *
     * Embedded relations are automatically cascaded when the parent data is removed.
     */
    public function isEmbedded(): bool;

    /**
     * Whether this type represents a relation to another data entity.
     */
    public function isRelation(): bool;

    /**
     * Hook to set default values on an attribute of this type.
     *
     * This method is called when an attribute is created with this type,
     * allowing the type to configure default options, validation rules, etc.
     */
    public function setAttributeDefaults(AttributeInterface $attribute): void;
}
