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

namespace Sidus\EAVModelBundle\Bridge\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Family\FamilyInterface;

/**
 * Repository for EAV data entities.
 *
 * Provides common query methods for finding data entities with support
 * for family filtering and EAV-specific queries.
 *
 * @extends EntityRepository<DataInterface>
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataRepository extends EntityRepository
{
    /**
     * Creates a QueryBuilder for a specific family.
     */
    public function createFamilyQueryBuilder(FamilyInterface $family, string $alias = 'e'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere(sprintf('%s.family = :family', $alias))
            ->setParameter('family', $family);
    }

    /**
     * Finds a data entity by its identifier attribute.
     */
    public function findByIdentifier(FamilyInterface $family, mixed $identifier): ?DataInterface
    {
        $identifierAttribute = $family->getAttributeAsIdentifier();
        if ($identifierAttribute === null) {
            // Fall back to database ID
            return $this->findOneBy(['family' => $family, 'id' => $identifier]);
        }

        $qb = $this->createFamilyQueryBuilder($family);
        $alias = $qb->getRootAliases()[0];

        $qb->join(sprintf('%s.values', $alias), 'v')
            ->andWhere('v.attributeCode = :attrCode')
            ->setParameter('attrCode', $identifierAttribute->getCode());

        // Determine the value column based on the attribute type
        $databaseType = $identifierAttribute->getType()->getDatabaseType();
        $qb->andWhere(sprintf('v.%s = :identifier', $databaseType))
            ->setParameter('identifier', $identifier);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds data entities by family.
     *
     * @return DataInterface[]
     */
    public function findByFamily(FamilyInterface $family, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createFamilyQueryBuilder($family);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Counts data entities by family.
     */
    public function countByFamily(FamilyInterface $family): int
    {
        $qb = $this->createFamilyQueryBuilder($family, 'e');
        $qb->select('COUNT(e.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Finds root data entities (without parent) by family.
     *
     * @return DataInterface[]
     */
    public function findRootsByFamily(FamilyInterface $family): array
    {
        $qb = $this->createFamilyQueryBuilder($family);
        $alias = $qb->getRootAliases()[0];
        $qb->andWhere(sprintf('%s.parent IS NULL', $alias));

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds children of a data entity.
     *
     * @return DataInterface[]
     */
    public function findChildren(DataInterface $parent, ?FamilyInterface $childFamily = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.parent = :parent')
            ->setParameter('parent', $parent);

        if ($childFamily !== null) {
            $qb->andWhere('e.family = :family')
                ->setParameter('family', $childFamily);
        }

        return $qb->getQuery()->getResult();
    }
}
