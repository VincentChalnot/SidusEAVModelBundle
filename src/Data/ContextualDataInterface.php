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
 * Interface for context-aware data entities.
 *
 * Contextual data supports multiple values per attribute based on context
 * (e.g., locale, channel, version).
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualDataInterface extends DataInterface
{
    /**
     * Returns the current context.
     *
     * @return array<string, mixed>
     */
    public function getCurrentContext(): array;

    /**
     * Sets the current context.
     *
     * @param array<string, mixed> $context
     * @throws ContextException
     */
    public function setCurrentContext(array $context): void;

    /**
     * Returns the valid context keys for this data type.
     *
     * @return string[]
     */
    public static function getContextKeys(): array;
}
