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
 * Exception thrown when there is an issue with context handling.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextException extends \RuntimeException implements EAVExceptionInterface
{
    /**
     * @param string[] $missingKeys
     */
    public static function missingKeys(array $missingKeys): self
    {
        return new self(sprintf('Missing key(s) in context: %s', implode(', ', $missingKeys)));
    }

    /**
     * @param string[] $extraKeys
     */
    public static function extraKeys(array $extraKeys): self
    {
        return new self(sprintf('Extra key(s) in context: %s', implode(', ', $extraKeys)));
    }

    public static function invalidKey(string $key): self
    {
        return new self(sprintf('Trying to access a non-allowed context key: %s', $key));
    }
}
