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

use Sidus\EAVModelBundle\Exception\AttributeConfigurationException;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Registry for global attributes.
 *
 * Global attributes are shared across families and serve as templates
 * for family-specific attributes.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeRegistry
{
    /**
     * Reserved attribute codes that cannot be used.
     * These are reserved for internal use or for native entity properties.
     */
    private const RESERVED_CODES = [
        'id',
        'identifier',
        'parent',
        'children',
        'values',
        'value',
        'valueData',
        'valuesData',
        'refererValues',
        'refererDatas',
        'family',
        'familyCode',
        'createdAt',
        'updatedAt',
        'currentContext',
        'label',
        'empty',
    ];

    /** @var array<string, AttributeInterface> */
    private array $attributes = [];

    /** @var array<string, array<string, mixed>> */
    private array $globalConfiguration = [];

    public function __construct(
        private readonly string $attributeClass,
        /** @var string[] */
        private readonly array $globalContextMask,
        private readonly AttributeTypeRegistry $attributeTypeRegistry,
        private readonly ?TranslatorInterface $translator = null,
    ) {
    }

    /**
     * Parse and store global configuration from the bundle config.
     *
     * @param array<string, array<string, mixed>> $config
     */
    public function parseGlobalConfig(array $config): void
    {
        $this->globalConfiguration = $config;
    }

    /**
     * Creates an attribute from configuration.
     *
     * @param array<string, mixed> $configuration
     * @throws AttributeConfigurationException
     */
    public function createAttribute(string $code, array $configuration = []): AttributeInterface
    {
        $this->validateCode($code);

        // Merge global configuration if available
        if (isset($this->globalConfiguration[$code])) {
            $configuration = array_merge($this->globalConfiguration[$code], $configuration);
        }

        // Determine the type
        $typeCode = $configuration['type'] ?? 'string';
        unset($configuration['type']);

        $type = $this->attributeTypeRegistry->getType($typeCode);

        // Create the attribute
        $attributeClass = $this->attributeClass;
        /** @var AttributeInterface $attribute */
        $attribute = new $attributeClass($code, $type, $this->translator);

        // Apply context mask
        if (!isset($configuration['context_mask'])) {
            $configuration['context_mask'] = $this->globalContextMask;
        }

        // Normalize configuration keys
        $configuration = $this->normalizeConfiguration($configuration);

        // Apply configuration
        $attribute->mergeConfiguration($configuration);

        return $attribute;
    }

    /**
     * Adds a pre-created attribute to the registry.
     */
    public function addAttribute(AttributeInterface $attribute): void
    {
        $this->validateCode($attribute->getCode());
        $this->attributes[$attribute->getCode()] = $attribute;
    }

    /**
     * Returns an attribute by its code.
     *
     * @throws MissingAttributeException
     */
    public function getAttribute(string $code): AttributeInterface
    {
        if (!isset($this->attributes[$code])) {
            throw MissingAttributeException::create($code);
        }

        return $this->attributes[$code];
    }

    /**
     * Checks if an attribute exists in the registry.
     */
    public function hasAttribute(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    /**
     * Returns all registered attributes.
     *
     * @return array<string, AttributeInterface>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns all attribute codes.
     *
     * @return string[]
     */
    public function getAttributeCodes(): array
    {
        return array_keys($this->attributes);
    }

    /**
     * Returns the global context mask.
     *
     * @return string[]
     */
    public function getGlobalContextMask(): array
    {
        return $this->globalContextMask;
    }

    /**
     * Checks if a code is reserved.
     */
    public function isReserved(string $code): bool
    {
        return in_array($code, self::RESERVED_CODES, true);
    }

    /**
     * Validates that a code is not reserved.
     *
     * @throws AttributeConfigurationException
     */
    private function validateCode(string $code): void
    {
        if ($this->isReserved($code)) {
            throw AttributeConfigurationException::reservedCode($code);
        }
    }

    /**
     * Normalize configuration keys from snake_case to camelCase.
     *
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function normalizeConfiguration(array $configuration): array
    {
        $normalized = [];

        foreach ($configuration as $key => $value) {
            // Convert snake_case to camelCase
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $normalized[$camelKey] = $value;
        }

        return $normalized;
    }
}
