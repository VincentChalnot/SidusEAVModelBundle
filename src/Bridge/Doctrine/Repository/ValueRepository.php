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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Attribute\AttributeInterface;
use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Data\ValueInterface;
use Sidus\EAVModelBundle\Family\FamilyInterface;

/**
 * Repository for EAV value entities.
 *
 * @extends EntityRepository<ValueInterface>
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ValueRepository extends EntityRepository
{
    /**
     * Creates a QueryBuilder for values of a specific attribute.
     */
    public function createAttributeQueryBuilder(AttributeInterface $attribute, string $alias = 'v'): QueryBuilder
    {
        $family = $attribute->getFamily();

        $qb = $this->createQueryBuilder($alias)
            ->andWhere(sprintf('%s.attributeCode = :attrCode', $alias))
            ->setParameter('attrCode', $attribute->getCode());

        if ($family !== null) {
            $qb->andWhere(sprintf('%s.familyCode = :familyCode', $alias))
                ->setParameter('familyCode', $family->getCode());
        }

        return $qb;
    }

    /**
     * Finds all values for a specific data entity.
     *
     * @return ValueInterface[]
     */
    public function findByData(DataInterface $data): array
    {
        return $this->findBy(['data' => $data], ['position' => 'ASC']);
    }

    /**
     * Finds values for a specific attribute and data entity.
     *
     * @return ValueInterface[]
     */
    public function findByDataAndAttribute(DataInterface $data, AttributeInterface $attribute): array
    {
        return $this->findBy(
            ['data' => $data, 'attributeCode' => $attribute->getCode()],
            ['position' => 'ASC']
        );
    }

    /**
     * Finds values that reference a specific data entity.
     *
     * @return ValueInterface[]
     */
    public function findRefererValues(DataInterface $data, ?FamilyInterface $family = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->where('v.dataValue = :data OR v.constrainedDataValue = :data')
            ->setParameter('data', $data);

        if ($family !== null) {
            $qb->andWhere('v.familyCode = :familyCode')
                ->setParameter('familyCode', $family->getCode());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Deletes orphan values (values without data).
     */
    public function deleteOrphans(): int
    {
        return $this->createQueryBuilder('v')
            ->delete()
            ->where('v.data IS NULL')
            ->getQuery()
            ->execute();
    }
}
