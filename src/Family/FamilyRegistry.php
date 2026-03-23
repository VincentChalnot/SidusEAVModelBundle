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

use Sidus\EAVModelBundle\Exception\MissingFamilyException;

/**
 * Registry for families.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyRegistry
{
    /** @var array<string, FamilyInterface> */
    private array $families = [];

    /**
     * Adds a family to the registry.
     */
    public function addFamily(FamilyInterface $family): void
    {
        $this->families[$family->getCode()] = $family;
    }

    /**
     * Returns a family by its code.
     *
     * @throws MissingFamilyException
     */
    public function getFamily(string $code): FamilyInterface
    {
        if (!isset($this->families[$code])) {
            throw MissingFamilyException::create($code);
        }

        return $this->families[$code];
    }

    /**
     * Checks if a family exists.
     */
    public function hasFamily(string $code): bool
    {
        return isset($this->families[$code]);
    }

    /**
     * Returns all registered families.
     *
     * @return array<string, FamilyInterface>
     */
    public function getFamilies(): array
    {
        return $this->families;
    }

    /**
     * Returns all family codes.
     *
     * @return string[]
     */
    public function getFamilyCodes(): array
    {
        return array_keys($this->families);
    }

    /**
     * Returns all root families (instantiable families with no parent).
     *
     * @return array<string, FamilyInterface>
     */
    public function getRootFamilies(): array
    {
        return array_filter(
            $this->families,
            static fn(FamilyInterface $family): bool => $family->isInstantiable() && $family->getParent() === null
        );
    }

    /**
     * Returns all child families of a given family.
     *
     * @return array<string, FamilyInterface>
     */
    public function getByParent(FamilyInterface $parent): array
    {
        return array_filter(
            $this->families,
            static fn(FamilyInterface $family): bool => $family->getParent()?->getCode() === $parent->getCode()
        );
    }

    /**
     * Returns all instantiable families.
     *
     * @return array<string, FamilyInterface>
     */
    public function getInstantiableFamilies(): array
    {
        return array_filter(
            $this->families,
            static fn(FamilyInterface $family): bool => $family->isInstantiable()
        );
    }
}
