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
 * Attribute type for identifier attributes.
 *
 * Identifier attributes are unique, required, and cannot be contextual.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class IdentifierAttributeType extends AttributeType
{
    public function setAttributeDefaults(AttributeInterface $attribute): void
    {
        // Identifiers must be unique and required
        // This is enforced at configuration level, but we can add hints here
    }
}
