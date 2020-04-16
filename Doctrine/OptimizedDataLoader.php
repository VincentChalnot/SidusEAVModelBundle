<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\ORM\PersistentCollection;
use Sidus\BaseBundle\Doctrine\RepositoryFinder;
use Sidus\EAVModelBundle\Entity\ContextualDataInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Optimize the loading of multiple data at once
 *
 * Warning, for related entities, this will only fetch the relations matching the current context
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class OptimizedDataLoader implements ContextualizedDataLoaderInterface
{
    /** @var RepositoryFinder */
    protected $repositoryFinder;

    /** @var array */
    protected $currentContext;

    /** @var array */
    protected $loadedEntityIds = [];

    /**
     * @param RepositoryFinder $repositoryFinder
     */
    public function __construct(RepositoryFinder $repositoryFinder)
    {
        $this->repositoryFinder = $repositoryFinder;
    }

    /**
     * @param array $context
     */
    public function setCurrentContext(array $context)
    {
        $this->currentContext = $context;
    }

    /**
     * @param DataInterface[] $entities
     * @param int             $depth
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function load($entities, $depth = 1)
    {
        if (!\is_array($entities) && !$entities instanceof \Traversable) {
            throw new \InvalidArgumentException(self::E_MSG);
        }

        $entitiesToLoad = [];
        foreach ($entities as $entity) {
            if (!$entity instanceof DataInterface) {
                throw new \InvalidArgumentException(self::E_MSG);
            }
            if (array_key_exists($entity->getId(), $this->loadedEntityIds)) {
                continue;
            }
            $entitiesToLoad[] = $entity;
            $this->loadedEntityIds[$entity->getId()] = null;

            if (null !== $this->currentContext && $entity instanceof ContextualDataInterface) {
                $entity->setCurrentContext($this->currentContext);
            }
        }

        $entitiesByValueClassByIds = $this->sortEntitiesByValueClass($entitiesToLoad);
        foreach ($entitiesByValueClassByIds as $valueClass => $entitiesById) {
            $this->loadEntityValues($valueClass, $entitiesById);
        }

        $this->loadRelatedEntities($entitiesToLoad, $depth);
    }

    /**
     * @param DataInterface|null $entity
     * @param int                $depth
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function loadSingle(DataInterface $entity = null, $depth = 2)
    {
        if (null === $entity) {
            return;
        }

        $this->load([$entity], $depth);
    }

    /**
     * @param DataInterface[] $entities
     *
     * @throws \InvalidArgumentException
     *
     * @return DataInterface[][]
     */
    protected function sortEntitiesByValueClass($entities)
    {
        $entitiesByValueClassByIds = [];
        foreach ($entities as $entity) {
            if (null === $entity) {
                continue;
            }
            if (!$entity instanceof DataInterface) {
                throw new \InvalidArgumentException(self::E_MSG);
            }
            if (null === $entity->getId()) {
                continue;
            }
            $entitiesByValueClassByIds[$entity->getFamily()->getValueClass()][$entity->getId()] = $entity;
        }

        return $entitiesByValueClassByIds;
    }

    /**
     * @param string          $valueClass
     * @param DataInterface[] $entitiesById
     *
     * @throws \UnexpectedValueException
     */
    protected function loadEntityValues($valueClass, array $entitiesById)
    {
        foreach ($this->getValues($valueClass, $entitiesById) as $valueEntity) {
            $data = $valueEntity->getData();

            $refl = new \ReflectionClass($data);
            $valuesProperty = $refl->getProperty('values');
            $valuesProperty->setAccessible(true);
            $dataValues = $valuesProperty->getValue($data);
            if ($dataValues instanceof PersistentCollection) {
                $dataValues->setInitialized(true);
                if (!$dataValues->contains($valueEntity)) {
                    $dataValues->add($valueEntity);
                }
            } else {
                throw new \UnexpectedValueException('Data.values is not an instance of PersistentCollection');
            }
        }
    }

    /**
     * @param string $valueClass
     * @param array  $entitiesById
     *
     * @return ValueInterface[]
     */
    protected function getValues($valueClass, array $entitiesById)
    {
        $valueRepository = $this->repositoryFinder->getRepository($valueClass);
        $qb = $valueRepository->createQueryBuilder('e');
        $qb
            ->addSelect('d', 'dv')
            ->join('e.data', 'd')
            ->leftJoin('e.dataValue', 'dv')
            ->where('e.data IN (:data)')
            ->setParameter('data', $entitiesById);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param DataInterface[] $entities
     * @param int             $depth
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    protected function loadRelatedEntities($entities, $depth)
    {
        if ($depth <= 0) {
            return;
        }
        $relatedEntities = [];
        foreach ($entities as $entity) {
            if (null === $entity) {
                continue;
            }
            $family = $entity->getFamily();
            foreach ($family->getAttributes() as $attribute) {
                foreach ($this->appendRelatedEntities($attribute, $entity) as $relatedEntity) {
                    $relatedEntities[] = $relatedEntity;
                }
            }
        }

        $this->load($relatedEntities, $depth - 1);
    }

    /**
     * @param AttributeInterface $attribute
     * @param DataInterface      $entity
     *
     * @return array
     */
    protected function appendRelatedEntities(
        AttributeInterface $attribute,
        DataInterface $entity
    ) {
        if (!$attribute->getOption('autoload', false) && !$attribute->getType()->isEmbedded()) {
            return [];
        }
        $relatedEntities = [];
        try {
            $relatedEntity = $entity->get($attribute->getCode());
            if (\is_array($relatedEntity) || $relatedEntity instanceof \Traversable) {
                foreach ($relatedEntity as $item) {
                    if ($item instanceof DataInterface) {
                        $relatedEntities[] = $item;
                    }
                }
            } elseif ($relatedEntity instanceof DataInterface) {
                $relatedEntities[] = $relatedEntity;
            }
        } catch (\Exception $e) {
            // Ignore exception
        }

        return $relatedEntities;
    }
}
