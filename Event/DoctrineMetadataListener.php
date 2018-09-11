<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Event;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;

/**
 * Change Doctrine's metadata on the fly to inject user-defined data and value classes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineMetadataListener
{
    const BASE_DATA_CLASS = DataInterface::class;
    const BASE_VALUE_CLASS = ValueInterface::class;

    /** @var array */
    protected $mapping;

    /**
     * @param string $dataClass
     * @param string $valueClass
     */
    public function __construct($dataClass, $valueClass)
    {
        $this->mapping = [
            static::BASE_DATA_CLASS => $dataClass,
            static::BASE_VALUE_CLASS => $valueClass,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        foreach ($metadata->associationMappings as $fieldName => $association) {
            if (!empty($association['targetEntity'])) {
                foreach ($this->mapping as $class => $override) {
                    if ($association['targetEntity'] === $class) {
                        $metadata->associationMappings[$fieldName]['targetEntity'] = $override;
                        continue;
                    }
                }
            }
            if (!empty($association['sourceEntity'])) {
                foreach ($this->mapping as $class => $override) {
                    if ($association['sourceEntity'] === $class) {
                        $metadata->associationMappings[$fieldName]['sourceEntity'] = $override;
                        continue;
                    }
                }
            }
        }
    }
}
