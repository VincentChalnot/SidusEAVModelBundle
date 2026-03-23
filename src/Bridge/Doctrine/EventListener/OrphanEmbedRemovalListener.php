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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Data\ValueInterface;
use Sidus\EAVModelBundle\Family\FamilyRegistry;

/**
 * Handles removal of embedded data entities.
 *
 * When a data entity with embedded relations is removed, this listener
 * ensures that the embedded entities are also properly removed.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class OrphanEmbedRemovalListener
{
    public function __construct(
        private readonly FamilyRegistry $familyRegistry,
    ) {
    }

    /**
     * Handles the preRemove event.
     */
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof DataInterface) {
            return;
        }

        $this->removeEmbeddedData($entity, $args->getObjectManager());
    }

    /**
     * Removes embedded data entities.
     */
    private function removeEmbeddedData(DataInterface $data, EntityManagerInterface $entityManager): void
    {
        $family = $data->getFamily();

        foreach ($family->getAttributes() as $attribute) {
            if (!$attribute->getType()->isEmbedded()) {
                continue;
            }

            foreach ($data->getValues($attribute) as $value) {
                $embeddedData = $this->getEmbeddedData($value);
                if ($embeddedData !== null) {
                    $entityManager->remove($embeddedData);
                }
            }
        }
    }

    /**
     * Gets the embedded data from a value.
     */
    private function getEmbeddedData(ValueInterface $value): ?DataInterface
    {
        return $value->getDataValue() ?? $value->getConstrainedDataValue();
    }
}
