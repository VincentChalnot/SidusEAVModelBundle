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

namespace Sidus\EAVModelBundle\Context;

/**
 * Interface for context management.
 *
 * The context manager handles the current runtime context for EAV operations.
 * Context typically includes things like locale, channel, version, etc.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextManagerInterface
{
    /**
     * Returns the current context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Sets the current context.
     *
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): void;

    /**
     * Returns the default context.
     *
     * @return array<string, mixed>
     */
    public function getDefaultContext(): array;

    /**
     * Returns a specific context value.
     */
    public function getContextValue(string $key): mixed;

    /**
     * Sets a specific context value.
     */
    public function setContextValue(string $key, mixed $value): void;

    /**
     * Merges context values into the current context.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed> The merged context
     */
    public function mergeContext(array $context): array;
}
