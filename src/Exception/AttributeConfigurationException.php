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
 * Exception thrown when attribute configuration is invalid.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeConfigurationException extends \InvalidArgumentException implements EAVExceptionInterface
{
    public static function reservedCode(string $code): self
    {
        return new self(sprintf('Attribute code "%s" is reserved and cannot be used', $code));
    }

    public static function invalidConfiguration(string $code, string $message): self
    {
        return new self(sprintf('Invalid configuration for attribute "%s": %s', $code, $message));
    }
}
