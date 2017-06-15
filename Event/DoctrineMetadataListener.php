<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
     * DoctrineMetadataListener constructor.
     *
     * @param string $dataClass
     * @param string $valueClass
     */
    public function __construct($dataClass, $valueClass)
    {
        $this->mapping = [
            self::BASE_DATA_CLASS => $dataClass,
            self::BASE_VALUE_CLASS => $valueClass,
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
