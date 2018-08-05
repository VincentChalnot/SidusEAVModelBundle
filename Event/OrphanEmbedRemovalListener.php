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

use Doctrine\ORM\Event\LifecycleEventArgs;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Automatically removes orphan embed data when the values holding them are deleted
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class OrphanEmbedRemovalListener
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param FamilyRegistry $familyRegistry
     */
    public function __construct(FamilyRegistry $familyRegistry)
    {
        $this->familyRegistry = $familyRegistry;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \Doctrine\ORM\ORMException
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $value = $args->getEntity();
        if (!$value instanceof ValueInterface) {
            return;
        }

        // Ignore removed families
        if (!$this->familyRegistry->hasFamily($value->getFamilyCode())) {
            return;
        }
        $family = $this->familyRegistry->getFamily($value->getFamilyCode());

        // Ignore removed attributes
        if (!$family->hasAttribute($value->getAttributeCode())) {
            return;
        }
        $attribute = $family->getAttribute($value->getAttributeCode());

        // Ignore non-embedded attributes
        if (!$attribute->getType()->isEmbedded()) {
            return;
        }

        // Ignore attributes marked for non orphan_removal
        if (!$attribute->getOption('orphan_removal', true)) {
            return;
        }

        $dataValue = $this->accessor->getValue($value, $attribute->getType()->getDatabaseType());
        $args->getEntityManager()->remove($dataValue);
    }
}
