<?php

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
        return new AttributeQueryBuilder($this, $attribute, $enforceFamilyCondition);
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
