<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sidus\EAVModelBundle\Entity\ValueInterface;

/**
 * Fetch entities involved in integrity constraints to an entity in order to check if the entity can be removed without
 * the database throwing an integrity constraint exception
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class IntegrityConstraintManager
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param mixed $sourceEntity
     *
     * @return array
     */
    public function getEntityConstraints($sourceEntity)
    {
        $className = ClassUtils::getClass($sourceEntity);
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($className);

        $associationsToCheck = $this->getConstrainedDataAssociations($em, $className);

        $constrainedEntities = [];
        foreach ($associationsToCheck as $associationMapping) {
            $entityRepository = $em->getRepository($associationMapping['sourceEntity']);
            $entities = $entityRepository->findBy(
                [
                    $associationMapping['fieldName'] => $sourceEntity,
                ]
            );
            foreach ($entities as $entity) {
                // Small hack for EAV values
                if ($entity instanceof ValueInterface) {
                    $entity = $entity->getData();
                }
                $constrainedEntities[] = $entity;
            }
        }

        return $constrainedEntities;
    }

    /**
     * @param EntityManagerInterface $em
     * @param string                 $className
     *
     * @return array
     */
    protected function getConstrainedDataAssociations(EntityManagerInterface $em, $className)
    {
        // We must ensure that we check all the different classes in case of inheritance mapping
        /** @var ClassMetadata $sourceMetadata */
        $sourceMetadata = $em->getClassMetadata($className);
        $classes = $sourceMetadata->parentClasses;
        $classes[] = $className;

        /** @var ClassMetadata[] $metadatas */
        $associationsToCheck = [];
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metadatas as $metadata) {
            foreach ($metadata->getAssociationMappings() as $associationMapping) {
                foreach ($classes as $class) {
                    $this->checkAssociationMapping($class, $associationMapping, $associationsToCheck);
                }
            }
        }

        return $associationsToCheck;
    }

    /**
     * @param string $className
     * @param array  $associationMapping
     * @param array  $associationsToCheck
     */
    protected function checkAssociationMapping($className, array $associationMapping, array &$associationsToCheck)
    {
        if (!is_a($associationMapping['targetEntity'], $className, true)) {
            return;
        }
        if (!isset($associationMapping['joinColumns'])) {
            return;
        }
        if (!\is_array($associationMapping['joinColumns'])) {
            return;
        }
        foreach ($associationMapping['joinColumns'] as $joinColumn) {
            if (isset($joinColumn['onDelete'])
                && \in_array(strtolower($joinColumn['onDelete']), ['cascade', 'set null'], true)
            ) {
                return;
            }
        }

        $associationsToCheck[] = $associationMapping;
    }
}
