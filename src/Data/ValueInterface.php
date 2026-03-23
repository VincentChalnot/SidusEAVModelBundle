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

use Sidus\EAVModelBundle\Attribute\AttributeInterface;

/**
 * Interface for EAV value entities.
 *
 * Value entities store the actual data for a single attribute value.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ValueInterface
{
    /**
     * Returns the unique identifier for this value.
     */
    public function getIdentifier(): int|string|null;

    /**
     * Returns the code of the attribute this value belongs to.
     */
    public function getAttributeCode(): string;

    /**
     * Returns the code of the family this value's attribute belongs to.
     */
    public function getFamilyCode(): string;

    /**
     * Returns the attribute this value belongs to.
     */
    public function getAttribute(): ?AttributeInterface;

    /**
     * Returns the data entity this value belongs to.
     */
    public function getData(): ?DataInterface;

    /**
     * Sets the data entity this value belongs to.
     */
    public function setData(?DataInterface $data): void;

    /**
     * Returns the position of this value within a collection.
     */
    public function getPosition(): ?int;

    /**
     * Sets the position of this value within a collection.
     */
    public function setPosition(?int $position): void;

    // Value getters and setters for different types

    public function getBoolValue(): ?bool;
    public function setBoolValue(?bool $boolValue): static;

    public function getIntegerValue(): ?int;
    public function setIntegerValue(?int $integerValue): static;

    public function getDecimalValue(): ?float;
    public function setDecimalValue(?float $decimalValue): static;

    public function getDateValue(): ?\DateTimeInterface;
    public function setDateValue(\DateTimeInterface|string|int|null $dateValue): static;

    public function getDatetimeValue(): ?\DateTimeInterface;
    public function setDatetimeValue(\DateTimeInterface|string|int|null $datetimeValue): static;

    public function getStringValue(): ?string;
    public function setStringValue(?string $stringValue): static;

    public function getTextValue(): ?string;
    public function setTextValue(?string $textValue): static;

    public function getDataValue(): ?DataInterface;
    public function setDataValue(?DataInterface $dataValue): static;

    public function getConstrainedDataValue(): ?DataInterface;
    public function setConstrainedDataValue(?DataInterface $constrainedDataValue): static;
}
