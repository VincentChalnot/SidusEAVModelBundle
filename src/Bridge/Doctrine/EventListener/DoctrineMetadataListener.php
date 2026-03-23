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

namespace Sidus\EAVModelBundle\Bridge\Doctrine\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Data\ValueInterface;

/**
 * Modifies Doctrine metadata to substitute the abstract target entities with concrete implementations.
 *
 * This listener replaces references to DataInterface and ValueInterface with
 * the actual configured entity classes.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineMetadataListener
{
    /** @var class-string<DataInterface> */
    protected readonly string $dataClass;

    /** @var class-string<ValueInterface> */
    protected readonly string $valueClass;

    private const TARGET_INTERFACES = [
        DataInterface::class,
        'Sidus\EAVModelBundle\Entity\DataInterface', // Legacy support
    ];

    private const VALUE_INTERFACES = [
        ValueInterface::class,
        'Sidus\EAVModelBundle\Entity\ValueInterface', // Legacy support
    ];

    /**
     * @param class-string<DataInterface> $dataClass
     * @param class-string<ValueInterface> $valueClass
     */
    public function __construct(string $dataClass, string $valueClass)
    {
        $this->dataClass = $dataClass;
        $this->valueClass = $valueClass;
    }

    /**
     * Handles the loadClassMetadata event.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->isMappedSuperclass) {
            return;
        }

        $this->processAssociationMappings($metadata);
    }

    /**
     * Processes association mappings and replaces interface references with concrete classes.
     */
    private function processAssociationMappings(ClassMetadata $metadata): void
    {
        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            $targetEntity = $mapping['targetEntity'] ?? null;

            if ($targetEntity === null) {
                continue;
            }

            // Replace DataInterface references
            if ($this->isDataInterface($targetEntity)) {
                $metadata->associationMappings[$fieldName]['targetEntity'] = $this->dataClass;
            }

            // Replace ValueInterface references
            if ($this->isValueInterface($targetEntity)) {
                $metadata->associationMappings[$fieldName]['targetEntity'] = $this->valueClass;
            }
        }
    }

    /**
     * Checks if a class name is the DataInterface or a legacy version of it.
     */
    private function isDataInterface(string $className): bool
    {
        return in_array($className, self::TARGET_INTERFACES, true);
    }

    /**
     * Checks if a class name is the ValueInterface or a legacy version of it.
     */
    private function isValueInterface(string $className): bool
    {
        return in_array($className, self::VALUE_INTERFACES, true);
    }
}
