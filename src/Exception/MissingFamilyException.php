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
 * Exception thrown when a family is not found in the registry.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingFamilyException extends \UnexpectedValueException implements EAVExceptionInterface
{
    public static function create(string $code): self
    {
        return new self(sprintf('No family with code "%s" found', $code));
    }
}
