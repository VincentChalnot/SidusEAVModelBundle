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

use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Build complex doctrine queries with the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVQueryBuilder implements EAVQueryBuilderInterface
{
    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var string */
    protected $alias;

    /** @var bool */
    protected $isApplied = false;

    /** @var array|null */
    protected $context;

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     */
    public function __construct(QueryBuilder $queryBuilder, $alias = 'e')
    {
        $this->queryBuilder = $queryBuilder;
        $this->alias = $alias;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param AttributeInterface $attribute
     * @param bool               $enforceFamilyCondition
     *
     * @return AttributeQueryBuilderInterface
     */
    public function attribute(AttributeInterface $attribute, $enforceFamilyCondition = true)
    {
        $attributeQb = new AttributeQueryBuilder($this, $attribute, $enforceFamilyCondition);
        if ($this->context) {
            $attributeQb->setContext($this->context);
        }

        return $attributeQb;
    }

    /**
     * @param DQLHandlerInterface[] $dqlHandlers
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     *
     * @return DQLHandlerInterface
     */
    public function getAnd(array $dqlHandlers)
    {
        return $this->getStatement(' AND ', $dqlHandlers);
    }

    /**
     * @param DQLHandlerInterface[] $dqlHandlers
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     *
     * @return DQLHandlerInterface
     */
    public function getOr(array $dqlHandlers)
    {
        return $this->getStatement(' OR ', $dqlHandlers);
    }

    /**
     * @param AttributeQueryBuilderInterface $attributeQueryBuilder
     * @param string                         $direction
     *
     * @return EAVQueryBuilder
     */
    public function addOrderBy(AttributeQueryBuilderInterface $attributeQueryBuilder, $direction = null)
    {
        $this->queryBuilder->addOrderBy($attributeQueryBuilder->applyJoin()->getColumn(), $direction);

        return $this;
    }

    /**
     * @param DQLHandlerInterface $DQLHandler
     *
     * @return QueryBuilder
     */
    public function apply(DQLHandlerInterface $DQLHandler)
    {
        $this->isApplied = true;

        $qb = $this->getQueryBuilder();

        if ($DQLHandler->getDQL()) {
            $qb->andWhere($DQLHandler->getDQL());
        }

        foreach ($DQLHandler->getParameters() as $key => $value) {
            $qb->setParameter($key, $value);
        }

        return $qb;
    }

    /**
     * @param array|null $context
     */
    public function setContext(array $context = null)
    {
        $this->context = $context;
    }

    /**
     * @param string                $glue
     * @param DQLHandlerInterface[] $dqlHandlers
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     *
     * @return DQLHandlerInterface
     */
    protected function getStatement($glue, array $dqlHandlers)
    {
        if ($this->isApplied) {
            throw new \LogicException('Query was already applied to query builder');
        }
        $dqlStatement = [];
        $parameters = [];
        foreach ($dqlHandlers as $dqlHandler) {
            if (!$dqlHandler instanceof DQLHandlerInterface) {
                throw new \UnexpectedValueException('$dqlHandlers parameters must be an array of DQLHandlerInterface');
            }
            $dqlStatement[] = '('.$dqlHandler->getDQL().')';

            foreach ($dqlHandler->getParameters() as $key => $value) {
                $parameters[$key] = $value;
            }
        }

        return new DQLHandler(implode($glue, $dqlStatement), $parameters);
    }
}
