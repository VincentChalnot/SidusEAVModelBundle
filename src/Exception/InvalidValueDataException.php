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
 * Exception thrown when the data provided to a value is invalid.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class InvalidValueDataException extends \RuntimeException implements EAVExceptionInterface
{
    public static function create(string $attributeCode, string $message): self
    {
        return new self(sprintf('Invalid value data for attribute "%s": %s', $attributeCode, $message));
    }
}
