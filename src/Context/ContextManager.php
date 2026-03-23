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
 * Default implementation of the context manager.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextManager implements ContextManagerInterface
{
    /** @var array<string, mixed> */
    private array $context;

    /**
     * @param array<string, mixed> $defaultContext
     */
    public function __construct(
        private readonly array $defaultContext = [],
    ) {
        $this->context = $this->defaultContext;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultContext(): array
    {
        return $this->defaultContext;
    }

    public function getContextValue(string $key): mixed
    {
        return $this->context[$key] ?? null;
    }

    public function setContextValue(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function mergeContext(array $context): array
    {
        return array_merge($this->context, $context);
    }
}
