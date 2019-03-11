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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is triggered when a change occurred to an EAV Data entity or one of it's values
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVEvent extends Event
{
    const STATE_CREATED = 1;
    const STATE_UPDATED = 2;
    const STATE_REMOVED = 3;

    /** @var DataInterface */
    protected $data;

    /** @var int */
    protected $state;

    /** @var array|null */
    protected $dataChangeset;

    /** @var ValueInterface[][] */
    protected $changedValues = [];

    /** @var ValueChangeset[][] */
    protected $valuesChangeset;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DataInterface          $data
     * @param int                    $state
     */
    public function __construct(EntityManagerInterface $entityManager, DataInterface $data, $state)
    {
        $this->entityManager = $entityManager;
        $this->data = $data;
        $this->state = $state;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return DataInterface
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param bool $forceRecompute
     *
     * @return array
     */
    public function getDataChangeset($forceRecompute = false)
    {
        if (null === $this->dataChangeset || $forceRecompute) {
            $this->computeDataChangeset();
        }

        return $this->dataChangeset;
    }

    /**
     * @param bool $forceRecompute
     *
     * @return ValueChangeset[][]
     */
    public function getValuesChangeset($forceRecompute = false)
    {
        if (null === $this->valuesChangeset || $forceRecompute) {
            $this->computeValuesChangeset();
        }

        return $this->valuesChangeset;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return ValueChangeset[]
     */
    public function getAttributeChangeset(AttributeInterface $attribute)
    {
        if (!array_key_exists($attribute->getCode(), $this->getValuesChangeset())) {
            return [];
        }

        return $this->getValuesChangeset()[$attribute->getCode()];
    }

    /**
     * @internal This method should only be called from the DoctrineToEAVEventConverter
     *
     * @param ValueInterface $value
     * @param int            $state
     */
    public function addChangedValue(ValueInterface $value, $state)
    {
        $this->changedValues[$state][] = $value;
    }

    /**
     * @param DataInterface $data
     */
    public function recomputeChangeset(DataInterface $data)
    {
        $uow = $this->entityManager->getUnitOfWork();
        if ($uow->isScheduledForDelete($data)) {
            return;
        }

        $valueClassMetadata = $this->entityManager->getClassMetadata($data->getFamily()->getValueClass());
        foreach ($data->getValuesCollection() as $value) {
            $this->doRecomputeChangeset($uow, $valueClassMetadata, $value);
        }

        $dataClassMetadata = $this->entityManager->getClassMetadata($data->getFamily()->getDataClass());
        $this->doRecomputeChangeset($uow, $dataClassMetadata, $data);
    }

    /**
     * @param DataInterface      $data
     * @param AttributeInterface $attribute
     */
    public function recomputeAttributeChangeset(DataInterface $data, AttributeInterface $attribute)
    {
        $uow = $this->entityManager->getUnitOfWork();
        if ($uow->isScheduledForDelete($data)) {
            return;
        }

        $valueClassMetadata = $this->entityManager->getClassMetadata($data->getFamily()->getValueClass());
        foreach ($data->getValuesCollection() as $value) {
            if ($value->getAttributeCode() === $attribute->getCode()) {
                $this->doRecomputeChangeset($uow, $valueClassMetadata, $value);
            }
        }
    }

    /**
     * @param UnitOfWork    $uow
     * @param ClassMetadata $classMetadata
     * @param object        $entity
     */
    protected function doRecomputeChangeset(UnitOfWork $uow, ClassMetadata $classMetadata, $entity)
    {
        if ($uow->isScheduledForDelete($entity)) {
            if ($entity instanceof ValueInterface && $entity->getData()) {
                $valuesCollection = $entity->getData()->getValuesCollection();
                if ($valuesCollection->contains($entity)) {
                    // Ensures the Data doesn't keep a reference to the value if the value was removed improperly
                    $entity->getData()->removeValue($entity);
                }
            }

            return;
        }
        $uow->persist($entity);
        if ($uow->getOriginalEntityData($entity)) {
            $uow->recomputeSingleEntityChangeSet($classMetadata, $entity);
        } else {
            $uow->computeChangeSet($classMetadata, $entity);
        }
    }

    /**
     * Compute the changeset of the entity
     */
    protected function computeDataChangeset()
    {
        $uow = $this->entityManager->getUnitOfWork();

        $this->dataChangeset = $uow->getEntityChangeSet($this->data);
    }

    /**
     * Compute the changeset of the entity
     */
    protected function computeValuesChangeset()
    {
        $uow = $this->entityManager->getUnitOfWork();

        $this->valuesChangeset = [];
        foreach ($this->changedValues as $state => $changedValues) {
            foreach ($changedValues as $changedValue) {
                $changeset = $uow->getEntityChangeSet($changedValue);
                $databaseType = $changedValue->getAttribute()->getType()->getDatabaseType();
                if (!array_key_exists($databaseType, $changeset)) {
                    throw new \LogicException('Change in value without change to value data');
                }

                $valueChangeset = new ValueChangeset(
                    $changedValue,
                    $changeset[$databaseType][0],
                    $state
                );
                $uid = spl_object_hash($changedValue);
                $this->valuesChangeset[$changedValue->getAttributeCode()][$uid] = $valueChangeset;
            }
        }
    }
}
