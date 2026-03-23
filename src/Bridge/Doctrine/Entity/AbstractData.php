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

namespace Sidus\EAVModelBundle\Bridge\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Data\ContextualDataInterface;
use Sidus\EAVModelBundle\Data\ContextualValueInterface;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Data\ValueInterface;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Exception\InvalidValueDataException;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Family\FamilyInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Abstract base class for EAV data entities using Doctrine ORM.
 *
 * This class provides the core EAV functionality for storing and retrieving
 * attribute values. Extend this class to create your concrete Data entity.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
#[ORM\MappedSuperclass]
abstract class AbstractData implements ContextualDataInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DataInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?DataInterface $parent = null;

    /** @var Collection<int, DataInterface> */
    #[ORM\OneToMany(targetEntity: DataInterface::class, mappedBy: 'parent', cascade: ['all'], orphanRemoval: true)]
    protected Collection $children;

    /** @var Collection<int, ValueInterface> */
    #[ORM\OneToMany(targetEntity: ValueInterface::class, mappedBy: 'data', cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    protected Collection $values;

    /** @var Collection<int, ValueInterface> */
    #[ORM\OneToMany(targetEntity: ValueInterface::class, mappedBy: 'dataValue', cascade: ['persist'])]
    protected Collection $refererValues;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected \DateTimeInterface $updatedAt;

    #[ORM\Column(name: 'family_code', type: 'sidus_family', length: 255)]
    protected FamilyInterface $family;

    /** @var array<string, mixed> Current runtime context */
    protected array $currentContext = [];

    /** @var array<string, array<int, ValueInterface>> Internal cache for values by attribute */
    protected array $valuesByAttributes = [];

    protected ?PropertyAccessorInterface $accessor = null;

    /**
     * @throws \LogicException If family is not instantiable
     * @throws InvalidValueDataException
     */
    public function __construct(FamilyInterface $family)
    {
        if (!$family->isInstantiable()) {
            throw new \LogicException(sprintf('Family "%s" is not instantiable', $family->getCode()));
        }

        $this->family = $family;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->values = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->refererValues = new ArrayCollection();

        // Create default values for attributes with defaults
        foreach ($family->getAttributes() as $attribute) {
            if ($attribute->getDefault() !== null) {
                $this->createDefaultValues($attribute);
            }
        }
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getIdentifier(): int|string|null
    {
        $identifierAttribute = $this->family->getAttributeAsIdentifier();
        if ($identifierAttribute !== null) {
            return $this->get($identifierAttribute->getCode());
        }

        return $this->getId();
    }

    public function getFamilyCode(): string
    {
        return $this->family->getCode();
    }

    public function getFamily(): FamilyInterface
    {
        return $this->family;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getParent(): ?DataInterface
    {
        return $this->parent;
    }

    public function setParent(?DataInterface $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getLabel(): string
    {
        $labelAttribute = $this->family->getAttributeAsLabel();
        if ($labelAttribute !== null) {
            try {
                $label = $this->get($labelAttribute->getCode());
                if ($label !== null) {
                    return (string) $label;
                }
            } catch (\Throwable) {
                // Fall through to default
            }
        }

        $identifier = $this->getIdentifier();
        return $identifier !== null ? (string) $identifier : sprintf('[%s]', $this->family->getCode());
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrentContext(): array
    {
        return $this->currentContext;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setCurrentContext(array $context): void
    {
        $this->currentContext = $context;
    }

    /**
     * @return string[]
     */
    public static function getContextKeys(): array
    {
        return [];
    }

    public function get(string $attributeCode, ?array $context = null): mixed
    {
        // Check for native getter
        $getter = 'get' . ucfirst($attributeCode);
        if (method_exists($this, $getter) && $attributeCode !== 'identifier' && $attributeCode !== 'label') {
            return $this->$getter();
        }

        $attribute = $this->family->getAttribute($attributeCode);

        if ($attribute->isCollection()) {
            return $this->getValuesData($attribute, $context);
        }

        return $this->getValueData($attribute, $context);
    }

    public function set(string $attributeCode, mixed $value, ?array $context = null): static
    {
        // Check for native setter
        $setter = 'set' . ucfirst($attributeCode);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
        }

        $attribute = $this->family->getAttribute($attributeCode);

        if ($attribute->isCollection()) {
            return $this->setValuesData($attribute, is_iterable($value) ? $value : [$value], $context);
        }

        return $this->setValueData($attribute, $value, $context);
    }

    public function add(string $attributeCode, mixed $value, ?array $context = null): static
    {
        $attribute = $this->family->getAttribute($attributeCode);

        if (!$attribute->isCollection()) {
            throw new \LogicException(sprintf('Cannot add to non-collection attribute "%s"', $attributeCode));
        }

        return $this->addValueData($attribute, $value, $context);
    }

    public function remove(string $attributeCode, mixed $value, ?array $context = null): static
    {
        $attribute = $this->family->getAttribute($attributeCode);

        if (!$attribute->isCollection()) {
            throw new \LogicException(sprintf('Cannot remove from non-collection attribute "%s"', $attributeCode));
        }

        $context = $this->resolveContext($context);
        foreach ($this->getValues($attribute, $context) as $v) {
            if ($this->getValueContent($v) === $value) {
                $this->removeValue($v);
                break;
            }
        }

        return $this;
    }

    /**
     * @return iterable<ValueInterface>
     */
    public function getValues(?AttributeInterface $attribute = null, ?array $context = null): iterable
    {
        if ($attribute === null) {
            return $this->values;
        }

        $context = $this->resolveContext($context);
        $values = [];

        foreach ($this->getValuesByAttribute($attribute) as $value) {
            if ($this->isValueMatchingContext($value, $context)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function getValue(AttributeInterface $attribute, ?array $context = null): ?ValueInterface
    {
        $context = $this->resolveContext($context);

        foreach ($this->getValuesByAttribute($attribute) as $value) {
            if ($this->isValueMatchingContext($value, $context)) {
                return $value;
            }
        }

        return null;
    }

    public function getValueData(AttributeInterface $attribute, ?array $context = null): mixed
    {
        $value = $this->getValue($attribute, $context);
        return $value !== null ? $this->getValueContent($value) : null;
    }

    /**
     * @return iterable<mixed>
     */
    public function getValuesData(AttributeInterface $attribute, ?array $context = null): iterable
    {
        $result = [];
        foreach ($this->getValues($attribute, $context) as $value) {
            $result[] = $this->getValueContent($value);
        }
        return $result;
    }

    public function setValueData(AttributeInterface $attribute, mixed $dataValue, ?array $context = null): static
    {
        $value = $this->getValue($attribute, $context);

        if ($dataValue === null) {
            if ($value !== null) {
                $this->removeValue($value);
            }
            return $this;
        }

        if ($value === null) {
            $value = $this->createValue($attribute, $context);
            $this->addValue($value);
        }

        $this->setValueContent($value, $dataValue);
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function setValuesData(AttributeInterface $attribute, iterable $dataValues, ?array $context = null): static
    {
        $this->emptyValues($attribute, $context);

        $position = 0;
        foreach ($dataValues as $dataValue) {
            if ($dataValue !== null) {
                $value = $this->createValue($attribute, $context);
                $value->setPosition($position++);
                $this->setValueContent($value, $dataValue);
                $this->addValue($value);
            }
        }

        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function emptyValues(?AttributeInterface $attribute = null, ?array $context = null): static
    {
        if ($attribute === null) {
            $this->values->clear();
            $this->valuesByAttributes = [];
            return $this;
        }

        foreach ($this->getValues($attribute, $context) as $value) {
            $this->removeValue($value);
        }

        return $this;
    }

    public function addValue(ValueInterface $value): static
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setData($this);
            $this->clearAttributeCache($value->getAttributeCode());
        }

        return $this;
    }

    public function addValueData(AttributeInterface $attribute, mixed $valueData, ?array $context = null): static
    {
        if ($valueData === null) {
            return $this;
        }

        $value = $this->createValue($attribute, $context);

        // Set position based on existing values
        $existingValues = iterator_to_array($this->getValues($attribute, $context));
        $value->setPosition(count($existingValues));

        $this->setValueContent($value, $valueData);
        $this->addValue($value);
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function removeValue(ValueInterface $value): static
    {
        $this->values->removeElement($value);
        $value->setData(null);
        $this->clearAttributeCache($value->getAttributeCode());

        return $this;
    }

    /**
     * @return iterable<ValueInterface>
     */
    public function getRefererValues(
        ?FamilyInterface $family = null,
        ?AttributeInterface $attribute = null,
        ?array $context = null
    ): iterable {
        $context = $this->resolveContext($context);
        $result = [];

        foreach ($this->refererValues as $value) {
            if ($family !== null && $value->getFamilyCode() !== $family->getCode()) {
                continue;
            }
            if ($attribute !== null && $value->getAttributeCode() !== $attribute->getCode()) {
                continue;
            }
            if ($context !== null && !$this->isValueMatchingContext($value, $context)) {
                continue;
            }
            $result[] = $value;
        }

        return $result;
    }

    /**
     * @return iterable<DataInterface>
     */
    public function getRefererDatas(
        ?FamilyInterface $family = null,
        ?AttributeInterface $attribute = null,
        ?array $context = null
    ): iterable {
        $datas = [];
        foreach ($this->getRefererValues($family, $attribute, $context) as $value) {
            $data = $value->getData();
            if ($data !== null && !in_array($data, $datas, true)) {
                $datas[] = $data;
            }
        }
        return $datas;
    }

    public function createValue(AttributeInterface $attribute, ?array $context = null): ValueInterface
    {
        $value = $this->family->createValue($this, $attribute);

        $context = $this->resolveContext($context);
        if ($value instanceof ContextualValueInterface && !empty($context)) {
            $value->setContext($context);
        }

        return $value;
    }

    public function isEmpty(AttributeInterface $attribute, ?array $context = null): bool
    {
        $value = $this->getValue($attribute, $context);
        if ($value === null) {
            return true;
        }

        $content = $this->getValueContent($value);
        return $content === null || $content === '' || $content === [];
    }

    public function __toString(): string
    {
        try {
            return $this->getLabel();
        } catch (\Throwable) {
            return sprintf('[%s]', $this->family->getCode());
        }
    }

    /**
     * Handle magic property access for attributes.
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Handle magic property access for attributes.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Check if an attribute has a value.
     */
    public function __isset(string $name): bool
    {
        try {
            $attribute = $this->family->getAttribute($name);
            return !$this->isEmpty($attribute);
        } catch (MissingAttributeException) {
            return false;
        }
    }

    /**
     * Clone support.
     */
    public function __clone()
    {
        $this->id = null;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->valuesByAttributes = [];

        // Clone values
        $originalValues = $this->values;
        $this->values = new ArrayCollection();
        foreach ($originalValues as $value) {
            $clonedValue = clone $value;
            $this->values->add($clonedValue);
            $clonedValue->setData($this);
        }

        // Clone children
        $originalChildren = $this->children;
        $this->children = new ArrayCollection();
        foreach ($originalChildren as $child) {
            $clonedChild = clone $child;
            $this->children->add($clonedChild);
            $clonedChild->setParent($this);
        }

        $this->refererValues = new ArrayCollection();
    }

    /**
     * Debug info.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'id' => $this->id,
            'family' => $this->family->getCode(),
            'label' => $this->getLabel(),
            'valuesCount' => $this->values->count(),
        ];
    }

    /**
     * Creates default values for an attribute.
     */
    protected function createDefaultValues(AttributeInterface $attribute): void
    {
        $default = $attribute->getDefault();
        if ($default === null) {
            return;
        }

        if ($attribute->isCollection()) {
            $defaults = is_iterable($default) ? $default : [$default];
            foreach ($defaults as $defaultValue) {
                $this->addValueData($attribute, $defaultValue);
            }
        } else {
            $this->setValueData($attribute, $default);
        }
    }

    /**
     * Returns values for a specific attribute (cached).
     *
     * @return array<int, ValueInterface>
     */
    protected function getValuesByAttribute(AttributeInterface $attribute): array
    {
        $code = $attribute->getCode();

        if (!isset($this->valuesByAttributes[$code])) {
            $this->valuesByAttributes[$code] = [];
            foreach ($this->values as $value) {
                if ($value->getAttributeCode() === $code) {
                    $this->valuesByAttributes[$code][] = $value;
                }
            }
        }

        return $this->valuesByAttributes[$code];
    }

    /**
     * Clears the attribute cache for a specific code.
     */
    protected function clearAttributeCache(string $code): void
    {
        unset($this->valuesByAttributes[$code]);
    }

    /**
     * Resolves the context to use, falling back to current context.
     *
     * @param array<string, mixed>|null $context
     * @return array<string, mixed>
     */
    protected function resolveContext(?array $context): array
    {
        return $context ?? $this->currentContext;
    }

    /**
     * Checks if a value matches the given context.
     *
     * @param array<string, mixed> $context
     */
    protected function isValueMatchingContext(ValueInterface $value, array $context): bool
    {
        if (empty($context) || !($value instanceof ContextualValueInterface)) {
            return true;
        }

        $valueContext = $value->getContext();
        foreach ($context as $key => $contextValue) {
            if (isset($valueContext[$key]) && $valueContext[$key] !== $contextValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the typed content of a value based on its attribute type.
     */
    protected function getValueContent(ValueInterface $value): mixed
    {
        $attribute = $value->getAttribute();
        if ($attribute === null) {
            return null;
        }

        $databaseType = $attribute->getType()->getDatabaseType();

        return match ($databaseType) {
            'boolValue' => $value->getBoolValue(),
            'integerValue' => $value->getIntegerValue(),
            'decimalValue' => $value->getDecimalValue(),
            'dateValue' => $value->getDateValue(),
            'datetimeValue' => $value->getDatetimeValue(),
            'stringValue' => $value->getStringValue(),
            'textValue' => $value->getTextValue(),
            'dataValue' => $attribute->getOption('constrained', false)
                ? $value->getConstrainedDataValue()
                : $value->getDataValue(),
            default => $value->getStringValue(),
        };
    }

    /**
     * Sets the typed content of a value based on its attribute type.
     */
    protected function setValueContent(ValueInterface $value, mixed $content): void
    {
        $attribute = $value->getAttribute();
        if ($attribute === null) {
            return;
        }

        $databaseType = $attribute->getType()->getDatabaseType();

        match ($databaseType) {
            'boolValue' => $value->setBoolValue((bool) $content),
            'integerValue' => $value->setIntegerValue((int) $content),
            'decimalValue' => $value->setDecimalValue((float) $content),
            'dateValue' => $value->setDateValue($content),
            'datetimeValue' => $value->setDatetimeValue($content),
            'stringValue' => $value->setStringValue($content !== null ? (string) $content : null),
            'textValue' => $value->setTextValue($content !== null ? (string) $content : null),
            'dataValue' => $attribute->getOption('constrained', false)
                ? $value->setConstrainedDataValue($content)
                : $value->setDataValue($content),
            default => $value->setStringValue($content !== null ? (string) $content : null),
        };
    }

    /**
     * Returns the property accessor.
     */
    protected function getAccessor(): PropertyAccessorInterface
    {
        if ($this->accessor === null) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }
}
