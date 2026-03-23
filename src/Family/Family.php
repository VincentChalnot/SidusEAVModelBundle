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

namespace Sidus\EAVModelBundle\Family;

use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Attribute\AttributeRegistry;
use Sidus\EAVModelBundle\Context\ContextManagerInterface;
use Sidus\EAVModelBundle\Data\ContextualValueInterface;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Data\ValueInterface;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Default implementation of a family.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Family implements FamilyInterface
{
    protected ?string $label = null;
    protected ?string $type = null;

    /** @var array<string, AttributeInterface> */
    protected array $attributes = [];

    protected ?FamilyInterface $parent = null;

    /** @var array<string, FamilyInterface> */
    protected array $children = [];

    protected ?AttributeInterface $attributeAsLabel = null;
    protected ?AttributeInterface $attributeAsIdentifier = null;

    protected bool $instantiable = true;
    protected bool $singleton = false;

    /** @var array<string, mixed> */
    protected array $options = [];

    protected ?TranslatorInterface $translator = null;

    /**
     * @param array<string, mixed>|null $config
     * @throws \UnexpectedValueException
     * @throws MissingFamilyException
     * @throws MissingAttributeException
     */
    public function __construct(
        protected readonly string $code,
        protected readonly AttributeRegistry $attributeRegistry,
        protected readonly FamilyRegistry $familyRegistry,
        protected readonly ContextManagerInterface $contextManager,
        protected string $dataClass,
        protected string $valueClass,
        ?array $config = null,
    ) {
        if ($config !== null) {
            $this->configure($config);
        }
    }

    public function setTranslator(?TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getCode(): string
    {
        return $this->code;
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

    public function getType(): ?string
    {
        return $this->type ?? $this->code;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getDataClass(): string
    {
        return $this->dataClass;
    }

    public function setDataClass(string $dataClass): void
    {
        $this->dataClass = $dataClass;
    }

    public function getValueClass(): string
    {
        return $this->valueClass;
    }

    public function setValueClass(string $valueClass): void
    {
        $this->valueClass = $valueClass;
    }

    public function getParent(): ?FamilyInterface
    {
        return $this->parent;
    }

    public function setParent(?FamilyInterface $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return array<string, FamilyInterface>
     */
    public function getChildren(): array
    {
        if (empty($this->children)) {
            $this->children = $this->familyRegistry->getByParent($this);
        }

        return $this->children;
    }

    /**
     * @return array<string, AttributeInterface>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $code): AttributeInterface
    {
        if (!isset($this->attributes[$code])) {
            throw MissingAttributeException::create($code, $this->code);
        }

        return $this->attributes[$code];
    }

    public function hasAttribute(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    public function addAttribute(AttributeInterface $attribute): void
    {
        $attribute->setFamily($this);
        $this->attributes[$attribute->getCode()] = $attribute;
    }

    public function getAttributeAsLabel(): ?AttributeInterface
    {
        return $this->attributeAsLabel;
    }

    public function setAttributeAsLabel(?AttributeInterface $attributeAsLabel): void
    {
        $this->attributeAsLabel = $attributeAsLabel;
    }

    public function getAttributeAsIdentifier(): ?AttributeInterface
    {
        return $this->attributeAsIdentifier;
    }

    public function setAttributeAsIdentifier(?AttributeInterface $attributeAsIdentifier): void
    {
        $this->attributeAsIdentifier = $attributeAsIdentifier;
    }

    public function isInstantiable(): bool
    {
        return $this->instantiable;
    }

    public function setInstantiable(bool $instantiable): void
    {
        $this->instantiable = $instantiable;
    }

    public function isSingleton(): bool
    {
        return $this->singleton;
    }

    public function setSingleton(bool $singleton): void
    {
        $this->singleton = $singleton;
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

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->contextManager->getContext();
    }

    public function createData(): DataInterface
    {
        if (!$this->isInstantiable()) {
            throw new \LogicException(sprintf('Family "%s" is not instantiable', $this->code));
        }

        $dataClass = $this->getDataClass();

        return new $dataClass($this);
    }

    public function createValue(DataInterface $data, AttributeInterface $attribute): ValueInterface
    {
        $valueClass = $this->getValueClass();

        return new $valueClass($data, $attribute);
    }

    /**
     * @return string[]
     */
    public function getMatchingCodes(string $pattern): array
    {
        $matching = [];

        foreach ($this->attributes as $code => $attribute) {
            if (fnmatch($pattern, $code)) {
                $matching[] = $code;
            }
        }

        return $matching;
    }

    /**
     * Configures this family from an array of configuration.
     *
     * @param array<string, mixed> $config
     * @throws MissingFamilyException
     * @throws MissingAttributeException
     * @throws \UnexpectedValueException
     */
    protected function configure(array $config): void
    {
        // Handle parent inheritance
        if (!empty($config['parent'])) {
            $parent = $this->familyRegistry->getFamily($config['parent']);
            $this->setParent($parent);
            $this->copyFromParent($parent);
        }
        unset($config['parent']);

        // Handle attributes
        if (isset($config['attributes'])) {
            $this->buildAttributes($config['attributes']);
        }
        unset($config['attributes']);

        // Handle attributeAsLabel
        if (!empty($config['attributeAsLabel'])) {
            $labelCode = $config['attributeAsLabel'];
            if (!$this->hasAttribute($labelCode)) {
                throw new \UnexpectedValueException(
                    sprintf('Attribute "%s" set as label does not exist in family "%s"', $labelCode, $this->code)
                );
            }
            $this->attributeAsLabel = $this->getAttribute($labelCode);
        }
        unset($config['attributeAsLabel']);

        // Handle attributeAsIdentifier
        if (!empty($config['attributeAsIdentifier'])) {
            $identifierCode = $config['attributeAsIdentifier'];
            if (!$this->hasAttribute($identifierCode)) {
                throw new \UnexpectedValueException(
                    sprintf('Attribute "%s" set as identifier does not exist in family "%s"', $identifierCode, $this->code)
                );
            }
            $identifierAttr = $this->getAttribute($identifierCode);

            if (!$identifierAttr->isUnique()) {
                throw new \UnexpectedValueException(
                    sprintf('Attribute "%s" set as identifier must be unique in family "%s"', $identifierCode, $this->code)
                );
            }
            if (!$identifierAttr->isRequired()) {
                throw new \UnexpectedValueException(
                    sprintf('Attribute "%s" set as identifier must be required in family "%s"', $identifierCode, $this->code)
                );
            }
            if ($identifierAttr->isCollection()) {
                throw new \UnexpectedValueException(
                    sprintf('Attribute "%s" set as identifier cannot be a collection in family "%s"', $identifierCode, $this->code)
                );
            }
            if (count($identifierAttr->getContextMask()) > 0) {
                throw new \UnexpectedValueException(
                    sprintf('Attribute "%s" set as identifier cannot be contextual in family "%s"', $identifierCode, $this->code)
                );
            }

            $this->attributeAsIdentifier = $identifierAttr;
        }
        unset($config['attributeAsIdentifier']);

        // Apply remaining configuration
        foreach ($config as $key => $value) {
            $setter = 'set' . ucfirst($this->camelize($key));
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif ($key === 'options' && is_array($value)) {
                $this->options = array_merge($this->options, $value);
            }
        }

        // Validate value class context
        $valueClass = $this->getValueClass();
        if (is_a($valueClass, ContextualValueInterface::class, true)) {
            $valueClass::checkContext($this->contextManager->getDefaultContext());
        }
    }

    /**
     * Copies configuration from a parent family.
     */
    protected function copyFromParent(FamilyInterface $parent): void
    {
        // Copy attributes
        foreach ($parent->getAttributes() as $attribute) {
            $this->attributes[$attribute->getCode()] = clone $attribute;
            $this->attributes[$attribute->getCode()]->setFamily($this);
        }

        // Copy settings
        if ($parent->getAttributeAsLabel() !== null) {
            $this->attributeAsLabel = $this->attributes[$parent->getAttributeAsLabel()->getCode()] ?? null;
        }

        if ($parent->getAttributeAsIdentifier() !== null) {
            $this->attributeAsIdentifier = $this->attributes[$parent->getAttributeAsIdentifier()->getCode()] ?? null;
        }

        // Copy options (can be overridden)
        $this->options = array_merge($parent->getOptions(), $this->options);
    }

    /**
     * Builds attributes from configuration.
     *
     * @param array<string, array<string, mixed>> $attributesConfig
     */
    protected function buildAttributes(array $attributesConfig): void
    {
        foreach ($attributesConfig as $code => $attributeConfig) {
            if ($attributeConfig === null) {
                $attributeConfig = [];
            }

            // Check if attribute already exists (from parent)
            if (isset($this->attributes[$code])) {
                $this->attributes[$code]->mergeConfiguration($attributeConfig);
            } else {
                $attribute = $this->attributeRegistry->createAttribute($code, $attributeConfig);
                $this->addAttribute($attribute);
            }
        }
    }

    /**
     * Returns the translation key for this family's label.
     */
    protected function getTranslationKey(): string
    {
        return "eav.family.{$this->code}.label";
    }

    /**
     * Converts a snake_case string to camelCase.
     */
    protected function camelize(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
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
            'parent' => $this->parent?->getCode(),
            'dataClass' => $this->dataClass,
            'valueClass' => $this->valueClass,
            'instantiable' => $this->instantiable,
            'singleton' => $this->singleton,
            'attributes' => array_keys($this->attributes),
            'attributeAsLabel' => $this->attributeAsLabel?->getCode(),
            'attributeAsIdentifier' => $this->attributeAsIdentifier?->getCode(),
        ];
    }
}
