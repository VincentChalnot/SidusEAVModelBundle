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

namespace Sidus\EAVModelBundle\Data;

use Sidus\EAVModelBundle\Exception\ContextException;

/**
 * Interface for context-aware value entities.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualValueInterface extends ValueInterface
{
    /**
     * Returns the current context of this value.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Sets the context of this value.
     *
     * @param array<string, mixed> $context
     * @throws ContextException
     */
    public function setContext(array $context): void;

    /**
     * Clears all context values.
     */
    public function clearContext(): void;

    /**
     * Gets a specific context value.
     *
     * @throws ContextException
     */
    public function getContextValue(string $key): mixed;

    /**
     * Sets a specific context value.
     *
     * @throws ContextException
     */
    public function setContextValue(string $key, mixed $value): void;

    /**
     * Returns the valid context keys for this value type.
     *
     * @return string[]
     */
    public static function getContextKeys(): array;

    /**
     * Validates that a context is valid.
     *
     * @param array<string, mixed> $context
     * @throws ContextException
     */
    public static function checkContext(array $context): void;
}
