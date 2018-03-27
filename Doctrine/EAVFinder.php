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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Use this service as a wrapper of the EAVQueryBuilder API to find data based on attributes values
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVFinder
{
    public const FILTER_OPERATORS = [
        '=',
        '!=',
        '<>',
        '>',
        '<',
        '>=',
        '<=',
        'in',
        'not in',
        'like',
        'not like',
        'is null',
        'is not null',
    ];

    /** @var Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws MissingAttributeException
     *
     * @return DataInterface[]
     */
    public function findBy(FamilyInterface $family, array $filterBy, array $orderBy = [])
    {
        $qb = $this->getQb($family, $filterBy, $orderBy);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws NonUniqueResultException
     * @throws MissingAttributeException
     *
     * @return DataInterface
     */
    public function findOneBy(FamilyInterface $family, array $filterBy)
    {
        $qb = $this->getQb($family, $filterBy);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     * @return mixed
     */
    public function filterBy(FamilyInterface $family, array $filterBy, array $orderBy = [])
    {
        $qb = $this->getFilterByQb($family, $filterBy, $orderBy);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy Filters with format [['attribute1','operator1','value1'],
     *                                  ['attribute2','operator2','value2'], etc.]
     * @param array           $orderBy
     * @param string          $alias
     * @return QueryBuilder
     */
    public function getFilterByQb(FamilyInterface $family, array $filterBy, array $orderBy = [], $alias = 'e')
    {
        $eavQb = $this->getRepository($family)->createFamilyQueryBuilder($family, $alias);

        // Add order by
        foreach ($orderBy as $attributeCode => $direction) {
            $eavQb->addOrderBy($eavQb->a($attributeCode), $direction);
        }

        $dqlHandlers = [];
        foreach ($filterBy as $filter) {
            $attributeCode = $filter[0];
            $operator = $filter[1];
            $value = $filter[2];

            $attributeQb = $eavQb->a($attributeCode);
            $handleDefaultValues = true;
            switch ($operator) {
                case '=':

                    $dqlHandler = $attributeQb->equals($value);
                    break;
                case '!=':
                case '<>':
                    $dqlHandler = $attributeQb->notEquals($value);
                    break;
                case '>':
                    $dqlHandler = $attributeQb->gt($value);
                    break;
                case '<':
                    $dqlHandler = $attributeQb->lt($value);
                    break;
                case '>=':
                    $dqlHandler = $attributeQb->gte($value);
                    break;
                case '<=':
                    $dqlHandler = $attributeQb->lte($value);
                    break;
                case 'in':
                    $dqlHandler = $attributeQb->in($value);
                    $handleDefaultValues = false;
                    break;
                case 'not in':
                    $dqlHandler = $attributeQb->notIn($value);
                    $handleDefaultValues = false;
                    break;
                case 'like':
                    $dqlHandler = $attributeQb->like($value);
                    break;
                case 'not like':
                    $dqlHandler = $attributeQb->notLike($value);
                    break;
                case 'is null':
                    $dqlHandler = $attributeQb->isNull();
                    $handleDefaultValues = false;
                    break;
                case 'is not null':
                    $dqlHandler = $attributeQb->isNotNull();
                    $handleDefaultValues = false;
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid filter operator');
            }

            if ($handleDefaultValues
                && null !== $value
                && $value === $family->getAttribute($attributeCode)->getDefault()) {
                $dqlHandlers[] = $eavQb->getOr(
                    [
                        $dqlHandler,
                        $attributeQb->isNull(), // Handles default values not persisted to database
                    ]
                );
            } else {
                $dqlHandlers[] = $dqlHandler;
            }
        }

        return $eavQb->apply($eavQb->getAnd($dqlHandlers));
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     * @param string          $alias
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws MissingAttributeException
     *
     * @return QueryBuilder
     */
    public function getQb(FamilyInterface $family, array $filterBy, array $orderBy = [], $alias = 'e')
    {
        $fixedFilterBy = [];
        foreach ($filterBy as $attributeCode => $value) {
            if (is_array($value)) {
                $fixedFilterBy[] = [$attributeCode, 'in', $value];
            } else {
                $fixedFilterBy[] = [$attributeCode, '=', $value];
            }
        }

        return $this->getFilterByQb($family, $fixedFilterBy, $orderBy, $alias);
    }

    /**
     * @param FamilyInterface $family
     *
     * @return DataRepository
     */
    public function getRepository(FamilyInterface $family)
    {
        return $this->doctrine->getRepository($family->getDataClass());
    }
}
