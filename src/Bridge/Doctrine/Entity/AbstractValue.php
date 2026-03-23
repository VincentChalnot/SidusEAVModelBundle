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

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Data\ContextualValueInterface;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Exception\ContextException;

/**
 * Abstract base class for EAV value entities using Doctrine ORM.
 *
 * Value entities store the actual data for a single attribute value.
 * Extend this class to create your concrete Value entity.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
#[ORM\MappedSuperclass]
abstract class AbstractValue implements ContextualValueInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DataInterface::class, inversedBy: 'values', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'data_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?DataInterface $data = null;

    #[ORM\ManyToOne(targetEntity: DataInterface::class, inversedBy: 'refererValues', fetch: 'EAGER', cascade: ['persist', 'detach'])]
    #[ORM\JoinColumn(name: 'data_value_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?DataInterface $dataValue = null;

    /**
     * Same as dataValue but without the onDelete="CASCADE" for constrained relations.
     */
    #[ORM\ManyToOne(targetEntity: DataInterface::class, fetch: 'EAGER', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'constrained_data_value_id', referencedColumnName: 'id', nullable: true)]
    protected ?DataInterface $constrainedDataValue = null;

    #[ORM\Column(name: 'attribute_code', type: 'string', length: 255)]
    protected string $attributeCode;

    #[ORM\Column(name: 'family_code', type: 'string', length: 255)]
    protected string $familyCode;

    #[ORM\Column(name: 'position', type: 'integer', nullable: true)]
    protected ?int $position = null;

    #[ORM\Column(name: 'bool_value', type: 'boolean', nullable: true)]
    protected ?bool $boolValue = null;

    #[ORM\Column(name: 'integer_value', type: 'integer', nullable: true)]
    protected ?int $integerValue = null;

    #[ORM\Column(name: 'decimal_value', type: 'float', nullable: true)]
    protected ?float $decimalValue = null;

    #[ORM\Column(name: 'date_value', type: 'date', nullable: true)]
    protected ?\DateTimeInterface $dateValue = null;

    #[ORM\Column(name: 'datetime_value', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $datetimeValue = null;

    #[ORM\Column(name: 'string_value', type: 'string', length: 255, nullable: true)]
    protected ?string $stringValue = null;

    #[ORM\Column(name: 'text_value', type: 'text', nullable: true)]
    protected ?string $textValue = null;

    public function __construct(DataInterface $data, AttributeInterface $attribute)
    {
        $family = $attribute->getFamily();
        if ($family === null) {
            throw new \LogicException(sprintf(
                'Attribute "%s" does not have a configured family',
                $attribute->getCode()
            ));
        }

        $this->data = $data;
        $this->attributeCode = $attribute->getCode();
        $this->familyCode = $family->getCode();
    }

    public function getIdentifier(): int|string|null
    {
        return $this->id;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function getFamilyCode(): string
    {
        return $this->familyCode;
    }

    public function getAttribute(): ?AttributeInterface
    {
        if ($this->data === null) {
            return null;
        }

        try {
            return $this->data->getFamily()->getAttribute($this->attributeCode);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getData(): ?DataInterface
    {
        return $this->data;
    }

    public function setData(?DataInterface $data): void
    {
        $this->data = $data;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getBoolValue(): ?bool
    {
        return $this->boolValue;
    }

    public function setBoolValue(?bool $boolValue): static
    {
        $this->boolValue = $boolValue;
        return $this;
    }

    public function getIntegerValue(): ?int
    {
        return $this->integerValue;
    }

    public function setIntegerValue(?int $integerValue): static
    {
        $this->integerValue = $integerValue;
        return $this;
    }

    public function getDecimalValue(): ?float
    {
        return $this->decimalValue;
    }

    public function setDecimalValue(?float $decimalValue): static
    {
        $this->decimalValue = $decimalValue;
        return $this;
    }

    public function getDateValue(): ?\DateTimeInterface
    {
        return $this->dateValue;
    }

    public function setDateValue(\DateTimeInterface|string|int|null $dateValue): static
    {
        $this->dateValue = $this->parseDateTime($dateValue);
        return $this;
    }

    public function getDatetimeValue(): ?\DateTimeInterface
    {
        return $this->datetimeValue;
    }

    public function setDatetimeValue(\DateTimeInterface|string|int|null $datetimeValue): static
    {
        $this->datetimeValue = $this->parseDateTime($datetimeValue);
        return $this;
    }

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function setStringValue(?string $stringValue): static
    {
        if ($stringValue !== null && strlen($stringValue) > 255) {
            $stringValue = substr($stringValue, 0, 255);
        }
        $this->stringValue = $stringValue;
        return $this;
    }

    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    public function setTextValue(?string $textValue): static
    {
        $this->textValue = $textValue;
        return $this;
    }

    public function getDataValue(): ?DataInterface
    {
        return $this->dataValue;
    }

    public function setDataValue(?DataInterface $dataValue): static
    {
        $this->dataValue = $dataValue;
        return $this;
    }

    public function getConstrainedDataValue(): ?DataInterface
    {
        return $this->constrainedDataValue;
    }

    public function setConstrainedDataValue(?DataInterface $constrainedDataValue): static
    {
        $this->constrainedDataValue = $constrainedDataValue;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        $context = [];
        foreach (static::getContextKeys() as $key) {
            $context[$key] = $this->$key ?? null;
        }
        return $context;
    }

    /**
     * @return string[]
     */
    public static function getContextKeys(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $context
     * @throws ContextException
     */
    public static function checkContext(array $context): void
    {
        $contextKeys = static::getContextKeys();

        $missingKeys = array_diff($contextKeys, array_keys($context));
        if (count($missingKeys) > 0) {
            throw ContextException::missingKeys($missingKeys);
        }

        $extraKeys = array_diff(array_keys($context), $contextKeys);
        if (count($extraKeys) > 0) {
            throw ContextException::extraKeys($extraKeys);
        }
    }

    public function getContextValue(string $key): mixed
    {
        $this->checkContextKey($key);
        return $this->$key ?? null;
    }

    /**
     * @param array<string, mixed> $context
     * @throws ContextException
     */
    public function setContext(array $context): void
    {
        $this->clearContext();
        foreach ($context as $key => $value) {
            $this->setContextValue($key, $value);
        }
    }

    public function clearContext(): void
    {
        foreach (static::getContextKeys() as $key) {
            $this->$key = null;
        }
    }

    /**
     * @throws ContextException
     */
    public function setContextValue(string $key, mixed $value): void
    {
        $this->checkContextKey($key);
        $this->$key = $value;
    }

    /**
     * Clone support - removes id and clones embedded data.
     */
    public function __clone()
    {
        $this->id = null;
        $attribute = $this->getAttribute();
        if ($this->dataValue !== null && $attribute?->getType()->isEmbedded()) {
            $this->dataValue = clone $this->dataValue;
        }
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
            'attributeCode' => $this->attributeCode,
            'familyCode' => $this->familyCode,
            'position' => $this->position,
        ];
    }

    /**
     * Validates a context key.
     *
     * @throws ContextException
     */
    protected function checkContextKey(string $key): void
    {
        if (!in_array($key, static::getContextKeys(), true)) {
            throw ContextException::invalidKey($key);
        }
    }

    /**
     * Parses a datetime value from various formats.
     */
    protected function parseDateTime(\DateTimeInterface|string|int|null $value): ?\DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_int($value)) {
            return (new \DateTime())->setTimestamp($value);
        }

        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            throw new \UnexpectedValueException(sprintf('Unable to parse datetime: %s', $value), 0, $e);
        }
    }
}
