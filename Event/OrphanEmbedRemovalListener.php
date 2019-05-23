<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
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
    public function preRemove(LifecycleEventArgs $args)
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

        // Trigger for attributes marked for orphan_removal, default true for embedded
        if ($attribute->getOption('orphan_removal', $attribute->getType()->isEmbedded())) {
            $method = 'get'.ucfirst($attribute->getType()->getDatabaseType());
            $valueData = $value->$method();

            // We can't call $value->getValueData() because the value can't access the attribute object anymore
            if ($valueData) {
                $args->getEntityManager()->remove($valueData);
            }
        }
    }
}
