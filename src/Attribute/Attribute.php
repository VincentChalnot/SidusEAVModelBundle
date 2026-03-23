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
use Sidus\EAVModelBundle\Family\FamilyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Default implementation of an EAV attribute.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Attribute implements AttributeInterface
{
    protected ?FamilyInterface $family = null;
    protected ?string $label = null;
    protected ?string $group = null;

    /** @var array<string, mixed> */
    protected array $options = [];

    protected bool $required = false;
    protected bool $unique = false;
    protected bool $collection = false;

    /** @var array<int, array<string, mixed>> */
    protected array $validationRules = [];

    protected mixed $default = null;

    /** @var string[] */
    protected array $contextMask = [];

    public function __construct(
        protected readonly string $code,
        protected readonly AttributeTypeInterface $type,
        protected readonly ?TranslatorInterface $translator = null,
    ) {
        $this->type->setAttributeDefaults($this);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): AttributeTypeInterface
    {
        return $this->type;
    }

    public function getFamily(): ?FamilyInterface
    {
        return $this->family;
    }

    public function setFamily(FamilyInterface $family): void
    {
        $this->family = $family;
    }

    public function getLabel(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $transKey = $this->getTranslationKey();
        if ($this->translator !== null) {
            $translated = $this->translator->trans($transKey);
            if ($translated !== $transKey) {
                return $translated;
            }
        }

        return ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->code) ?? $this->code);
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOption(string $code, mixed $default = null): mixed
    {
        return $this->options[$code] ?? $default;
    }

    public function hasOption(string $code): bool
    {
        return array_key_exists($code, $this->options);
    }

    public function addOption(string $code, mixed $value): void
    {
        $this->options[$code] = $value;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function setCollection(bool $collection): void
    {
        $this->collection = $collection;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * @param array<int, array<string, mixed>> $validationRules
     */
    public function setValidationRules(array $validationRules): void
    {
        $this->validationRules = $validationRules;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function setDefault(mixed $default): void
    {
        $this->default = $default;
    }

    /**
     * @return string[]
     */
    public function getContextMask(): array
    {
        return $this->contextMask;
    }

    /**
     * @param string[] $contextMask
     */
    public function setContextMask(array $contextMask): void
    {
        $this->contextMask = $contextMask;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function isContextMatching(array $context): bool
    {
        if (empty($this->contextMask)) {
            return true;
        }

        foreach ($this->contextMask as $key) {
            if (!array_key_exists($key, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function mergeConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                // Store as option if not a direct property
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Returns the translation key for this attribute's label.
     */
    protected function getTranslationKey(): string
    {
        $familyCode = $this->family?->getCode() ?? 'global';
        return "eav.attribute.{$familyCode}.{$this->code}.label";
    }

    /**
     * Debug info for var_dump.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type->getCode(),
            'family' => $this->family?->getCode(),
            'required' => $this->required,
            'unique' => $this->unique,
            'collection' => $this->collection,
            'contextMask' => $this->contextMask,
            'options' => array_keys($this->options),
        ];
    }
}
