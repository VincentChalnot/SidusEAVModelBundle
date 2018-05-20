<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Tries to find an existing entity based on the provided data, fallback to create a new entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EntityProvider implements EntityProviderInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * This is a temporary storage, to get created entity for a given reference before they are persisted & flushed by
     * doctrine. It's mapped by family and reference
     *
     * @var DataInterface[][]
     */
    protected $createdEntities = [];

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param FamilyInterface        $family
     * @param mixed                  $data
     * @param NameConverterInterface $nameConverter
     *
     * @throws UnexpectedValueException
     *
     * @return DataInterface|null
     */
    public function getEntity(FamilyInterface $family, $data, NameConverterInterface $nameConverter = null)
    {
        /** @var DataRepository $repository */
        $repository = $this->doctrine->getRepository($family->getDataClass());

        if ($family->isSingleton()) {
            try {
                return $repository->getInstance($family);
            } catch (\Exception $e) {
                throw new UnexpectedValueException("Unable to get singleton for family {$family->getCode()}", 0, $e);
            }
        }

        // In case we are trying to resolve a simple reference
        if (is_scalar($data)) {
            try {
                $entity = $repository->findByIdentifier($family, $data, true);
            } catch (\Exception $e) {
                throw new UnexpectedValueException(
                    "Unable to resolve id/identifier {$data} for family {$family->getCode()}",
                    0,
                    $e
                );
            }
            if (!$entity) {
                throw new UnexpectedValueException(
                    "No entity found for {$family->getCode()} with identifier '{$data}'"
                );
            }

            return $entity;
        }

        if (!\is_array($data) && !$data instanceof \ArrayAccess) {
            throw new UnexpectedValueException('Unable to denormalize data from unknown format');
        }

        // If the id is set (and not null), don't even look for the identifier
        if (isset($data['id'])) {
            return $repository->find($data['id']);
        }

        // Try to resolve the identifier
        $reference = $this->resolveIdentifier($data, $family, $nameConverter);

        if (null !== $reference) {
            try {
                $entity = $repository->findByIdentifier($family, $reference);
                if ($entity) {
                    return $entity;
                }
            } catch (\Exception $e) {
                throw new UnexpectedValueException("Unable to resolve identifier {$reference}", 0, $e);
            }
        }

        // Maybe the entity already exists but is not yet persisted
        if (null !== $reference && $this->hasCreatedEntity($family, $reference)) {
            return $this->getCreatedEntity($family, $reference);
        }

        $entity = $family->createData();

        // If we can, store the created entity for later
        if (null !== $reference) {
            $this->addCreatedEntity($entity, $reference);
        }

        return $entity;
    }

    /**
     * When an entity is created, we don't need to keep the reference anymore
     *
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $uow = $event->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof DataInterface
                && $this->hasCreatedEntity(
                    $entity->getFamily(),
                    $entity->getIdentifier()
                )
            ) {
                $this->removeCreatedEntity($entity->getFamily(), $entity->getIdentifier());
            }
        }
    }

    /**
     * @param array|\ArrayAccess     $data
     * @param FamilyInterface        $family
     * @param NameConverterInterface $nameConverter
     *
     * @return mixed
     */
    protected function resolveIdentifier(
        array $data,
        FamilyInterface $family,
        NameConverterInterface $nameConverter = null
    ) {
        if (!$family->getAttributeAsIdentifier()) {
            return null;
        }
        $attributeCode = $family->getAttributeAsIdentifier()->getCode();
        if ($nameConverter) {
            $attributeCode = $nameConverter->normalize($attributeCode);
        }
        if (array_key_exists($attributeCode, $data)) {
            return $data[$attributeCode];
        }

        return null;
    }

    /**
     * @param FamilyInterface $family
     * @param int|string      $reference
     *
     * @return DataInterface
     */
    protected function getCreatedEntity(FamilyInterface $family, $reference)
    {
        if (!$this->hasCreatedEntity($family, $reference)) {
            return null;
        }

        return $this->createdEntities[$family->getCode()][$reference];
    }

    /**
     * @param DataInterface $entity
     * @param int|string    $reference
     */
    protected function addCreatedEntity(DataInterface $entity, $reference)
    {
        $family = $entity->getFamily();

        if (!array_key_exists($family->getCode(), $this->createdEntities)) {
            $this->createdEntities[$family->getCode()] = [];
        }

        $this->createdEntities[$family->getCode()][$reference] = $entity;
    }

    /**
     * @param FamilyInterface $family
     * @param int|string      $reference
     *
     * @return bool
     */
    protected function hasCreatedEntity(FamilyInterface $family, $reference)
    {
        return array_key_exists($family->getCode(), $this->createdEntities)
            && array_key_exists(
                $reference,
                $this->createdEntities[$family->getCode()]
            );
    }

    /**
     * @param FamilyInterface $family
     * @param int|string      $reference
     */
    protected function removeCreatedEntity(FamilyInterface $family, $reference)
    {
        unset($this->createdEntities[$family->getCode()][$reference]);
    }
}
