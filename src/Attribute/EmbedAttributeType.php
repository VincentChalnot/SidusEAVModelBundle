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
 * Attribute type for embedded (owned) relations to other data entities.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EmbedAttributeType extends RelationAttributeType
{
    public function isEmbedded(): bool
    {
        return true;
    }
}
