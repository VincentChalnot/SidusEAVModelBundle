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

namespace Sidus\EAVModelBundle\Exception;

/**
 * Exception thrown when an attribute is not found in the registry or family.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingAttributeException extends \UnexpectedValueException implements EAVExceptionInterface
{
    public static function create(string $code, ?string $familyCode = null): self
    {
        if ($familyCode !== null) {
            return new self(sprintf('No attribute with code "%s" found in family "%s"', $code, $familyCode));
        }

        return new self(sprintf('No attribute with code "%s" found', $code));
    }
}
