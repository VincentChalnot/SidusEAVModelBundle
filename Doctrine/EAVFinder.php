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
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
    const FILTER_OPERATORS = [
        '=' => 'equals',
        '!=' => 'notEquals',
        '<>' => 'notEquals',
        '>' => 'gt',
        '<' => 'lt',
        '>=' => 'gte',
        '<=' => 'lte',
        'in' => 'in',
        'not in' => 'notIn',
        'like' => 'like',
        'not like' => 'notLike',
        'is null' => 'isNull',
        'is not null' => 'isNotNull',
    ];

    /** @var Registry */
    protected $doctrine;

    /** @var DataLoaderInterface */
    protected $dataLoader;

    /**
     * @param Registry            $doctrine
     * @param DataLoaderInterface $dataLoader
     */
    public function __construct(Registry $doctrine, DataLoaderInterface $dataLoader)
    {
        $this->doctrine = $doctrine;
        $this->dataLoader = $dataLoader;
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws MissingAttributeException
     *
     * @return DataInterface[]
     */
    public function findBy(FamilyInterface $family, array $filterBy, array $orderBy = [])
    {
        $qb = $this->getQb($family, $filterBy, $orderBy);
        $results = $qb->getQuery()->getResult();
        $this->dataLoader->load($results);

        return $results;
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws MissingAttributeException
     *
     * @return DataInterface
     */
    public function findOneBy(FamilyInterface $family, array $filterBy)
    {
        $qb = $this->getQb($family, $filterBy);
        $pager = new Paginator($qb);
        $pager->getQuery()->setMaxResults(1);

        $result = $pager->getIterator()->current();
        $this->dataLoader->loadSingle($result);

        return $result;
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function filterBy(FamilyInterface $family, array $filterBy, array $orderBy = [])
    {
        $qb = $this->getFilterByQb($family, $filterBy, $orderBy);

        $results = $qb->getQuery()->getResult();
        $this->dataLoader->load($results);

        return $results;
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy Filters with format [['attribute1','operator1','value1'],
     *                                  ['attribute2','operator2','value2'], etc.]
     * @param array           $orderBy
     * @param string          $alias
     *
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws \InvalidArgumentException
     *
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
            list($attributeCode, $operator, $value) = $filter;

            $attributeQb = $eavQb->a($attributeCode);
            $handleDefaultValues = true;

            if (!array_key_exists($operator, static::FILTER_OPERATORS)) {
                $m = "Invalid filter operator '{$operator}', valid operators are: ";
                $m .= implode(', ', array_keys(self::FILTER_OPERATORS));
                throw new \InvalidArgumentException($m);
            }

            $method = static::FILTER_OPERATORS[$operator];
            switch ($operator) {
                case 'is null':
                case 'is not null':
                    $dqlHandler = $attributeQb->$method();
                    $handleDefaultValues = false;
                    break;
                case 'in':
                    /** @noinspection PhpMissingBreakStatementInspection */
                case 'not in':
                    $handleDefaultValues = false;
                default:
                    $dqlHandler = $attributeQb->$method($value);
            }

            if ($handleDefaultValues
                && null !== $value
                && $value === $family->getAttribute($attributeCode)->getDefault()
            ) {
                $dqlHandlers[] = $eavQb->getOr(
                    [
                        $dqlHandler,
                        (clone $attributeQb)->isNull(), // Handles default values not yet persisted to database
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
     * @throws \InvalidArgumentException
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
            if (\is_array($value)) {
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
