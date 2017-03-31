<?php

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\ORM\Query\Expr\Join;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Applies logical conditions on attributes in the EAV Model for the Doctrine Query Builder
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeQueryBuilder extends DQLHandler implements AttributeQueryBuilderInterface
{
    /** @var EAVQueryBuilderInterface */
    protected $eavQueryBuilder;

    /** @var AttributeInterface */
    protected $attribute;

    /** @var string */
    protected $joinAlias;

    /**
     * @param EAVQueryBuilderInterface $eavQueryBuilder
     * @param AttributeInterface       $attribute
     */
    public function __construct(EAVQueryBuilderInterface $eavQueryBuilder, AttributeInterface $attribute)
    {
        $this->eavQueryBuilder = $eavQueryBuilder;
        $this->attribute = $attribute;
        $this->applyJoin();
    }

    /**
     * @return string
     * @throws \LogicException
     */
    public function getDQL()
    {
        if (null === $this->dql) {
            $msg = "No condition applied on attribute query builder for attribute {$this->attribute->getCode()}";
            throw new \LogicException($msg);
        }

        return $this->dql;
    }

    /**
     * @param array $array
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function in(array $array)
    {
        $parameterName = $this->generateUniqueId();

        return $this->rawDQL(
            "{$this->getColumn()} IN (:{$parameterName})",
            [
                $parameterName => $array,
            ]
        );
    }

    /**
     * @param array $array
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function notIn(array $array)
    {
        $parameterName = $this->generateUniqueId();

        return $this->rawDQL(
            "{$this->getColumn()} NOT IN (:{$parameterName})",
            [
                $parameterName => $array,
            ]
        );
    }

    /**
     * @param mixed $scalar
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function equals($scalar)
    {
        return $this->simpleDQLStatement('=', $scalar);
    }

    /**
     * @param mixed $scalar
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function notEquals($scalar)
    {
        return $this->simpleDQLStatement('!=', $scalar);
    }

    /**
     * @param mixed $scalar
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function like($scalar)
    {
        return $this->simpleDQLStatement('LIKE', $scalar);
    }

    /**
     * @param mixed $scalar
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function notLike($scalar)
    {
        return $this->simpleDQLStatement('NOT LIKE', $scalar);
    }

    /**
     * @param mixed $number
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function gt($number)
    {
        return $this->simpleDQLStatement('>', $number);
    }

    /**
     * @param mixed $number
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function gte($number)
    {
        return $this->simpleDQLStatement('>=', $number);
    }

    /**
     * @param mixed $number
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function lt($number)
    {
        return $this->simpleDQLStatement('<', $number);
    }

    /**
     * @param mixed $number
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function lte($number)
    {
        return $this->simpleDQLStatement('<=', $number);
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function between($lower, $upper)
    {
        $lowerParameterName = $this->generateUniqueId();
        $upperParameterName = $this->generateUniqueId();

        return $this->rawDQL(
            "{$this->getColumn()} BETWEEN :{$lowerParameterName} AND :{$upperParameterName}",
            [
                $lowerParameterName => $lower,
                $upperParameterName => $upper,
            ]
        );
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function notBetween($lower, $upper)
    {
        $lowerParameterName = $this->generateUniqueId();
        $upperParameterName = $this->generateUniqueId();

        return $this->rawDQL(
            "{$this->getColumn()} NOT BETWEEN :{$lowerParameterName} AND :{$upperParameterName}",
            [
                $lowerParameterName => $lower,
                $upperParameterName => $upper,
            ]
        );
    }

    /**
     * @param string $dql
     * @param array  $parameters
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    public function rawDQL($dql, array $parameters = [])
    {
        if (null !== $this->dql) {
            throw new \LogicException("Condition as already been applied {$this->dql}");
        }
        $this->dql = $dql;
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->joinAlias.'.'.$this->attribute->getType()->getDatabaseType();
    }

    /**
     * @param string $operator
     * @param        $parameter
     *
     * @throws \LogicException
     * @return AttributeQueryBuilderInterface
     */
    protected function simpleDQLStatement($operator, $parameter)
    {
        $parameterName = $this->generateUniqueId();

        return $this->rawDQL(
            "{$this->getColumn()} {$operator} :{$parameterName}",
            [
                $parameterName => $parameter,
            ]
        );
    }

    /**
     * Apply the join condition on the Query Builder
     */
    protected function applyJoin()
    {
        $alias = $this->eavQueryBuilder->getAlias();
        $this->joinAlias = $this->generateUniqueId('join');
        $qb = $this->eavQueryBuilder->getQueryBuilder();

        $joinCondition = "{$this->joinAlias}.attributeCode = '{$this->attribute->getCode()}'";
        $joinCondition .= " AND {$this->joinAlias}.familyCode = '{$this->attribute->getFamily()->getCode()}'";

        $qb->leftJoin(
            $alias.'.values',
            $this->joinAlias,
            Join::WITH,
            $joinCondition
        );
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function generateUniqueId($prefix = 'param')
    {
        return uniqid($prefix, false);
    }
}
