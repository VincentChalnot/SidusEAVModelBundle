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
 * Basic implementation of an attribute type.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeType implements AttributeTypeInterface
{
    public function __construct(
        protected readonly string $code,
        protected readonly string $databaseType,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDatabaseType(): string
    {
        return $this->databaseType;
    }

    public function isEmbedded(): bool
    {
        return false;
    }

    public function isRelation(): bool
    {
        return false;
    }

    public function setAttributeDefaults(AttributeInterface $attribute): void
    {
        // Override in subclasses to set defaults
    }
}
