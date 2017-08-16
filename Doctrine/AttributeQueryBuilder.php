<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\ORM\Query\Expr\Join;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
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

    /** @var bool */
    protected $enforceFamilyCondition;

    /** @var string */
    protected $joinAlias;

    /** @var bool */
    protected $joinApplied = false;

    /** @var bool */
    protected $skipJoin = false;

    /** @var bool */
    protected $joinRelation = false;

    /**
     * @param EAVQueryBuilderInterface $eavQueryBuilder
     * @param AttributeInterface       $attribute
     * @param bool                     $enforceFamilyCondition
     */
    public function __construct(
        EAVQueryBuilderInterface $eavQueryBuilder,
        AttributeInterface $attribute,
        $enforceFamilyCondition = true
    ) {
        $this->eavQueryBuilder = $eavQueryBuilder;
        $this->attribute = $attribute;
        $this->enforceFamilyCondition = $enforceFamilyCondition;
        $this->prepareJoin();
    }

    /**
     * @throws \LogicException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     *
     * @return string
     */
    public function getDQL()
    {
        if (null === $this->dql) {
            $msg = "No condition applied on attribute query builder for attribute {$this->attribute->getCode()}";
            throw new \LogicException($msg);
        }
        if (!$this->joinApplied) {
            $this->applyJoin();
        }

        return $this->dql;
    }

    /**
     * @param array $array
     *
     * @throws \LogicException
     *
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
     *
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
     *
     * @return AttributeQueryBuilderInterface
     */
    public function equals($scalar)
    {
        if (null === $scalar) {
            return $this->isNull(); // Special case for null, might not be a good idea to handle this here...
        }

        return $this->simpleDQLStatement('=', $scalar);
    }

    /**
     * @param mixed $scalar
     *
     * @throws \LogicException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function notEquals($scalar)
    {
        if (null === $scalar) {
            return $this->isNotNull(); // Special case for null, might not be a good idea to handle this here...
        }

        return $this->simpleDQLStatement('!=', $scalar);
    }

    /**
     * @param mixed $scalar
     *
     * @throws \LogicException
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     * @throws \LogicException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function isNull()
    {
        return $this->rawDQL("{$this->getColumn()} IS NULL");
    }

    /**
     * @throws \LogicException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function isNotNull()
    {
        return $this->rawDQL("{$this->getColumn()} IS NOT NULL");
    }

    /**
     * @param string $dql
     * @param array  $parameters
     *
     * @throws \LogicException
     *
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
     * Use this attribute to join on a related entity.
     * Returns an EAVQueryBuilder by default but can be used for other Doctrine entities too.
     *
     * @param string $alias
     *
     * @return EAVQueryBuilderInterface
     */
    public function join($alias = null)
    {
        if (null === $alias) {
            $alias = $this->generateUniqueId('join');
        }
        $this->joinRelation = $alias;
        $this->applyJoin();

        return new EAVQueryBuilder($this->eavQueryBuilder->getQueryBuilder(), $alias);
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->joinAlias.'.'.$this->attribute->getType()->getDatabaseType();
    }

    /**
     * Apply the join condition on the Query Builder
     *
     * @throws \LogicException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function applyJoin()
    {
        if ($this->skipJoin) {
            return $this;
        }
        $attributeCode = $this->attribute->getCode();
        if ($this->joinApplied) {
            throw new \LogicException("Join for attribute query builder {$attributeCode} already applied");
        }
        $qb = $this->eavQueryBuilder->getQueryBuilder();

        // Join based on attributeCode
        $attributeParameter = $this->generateUniqueId('attribute');
        $qb->setParameter($attributeParameter, $attributeCode);
        $joinDql = "{$this->joinAlias}.attributeCode = :{$attributeParameter}";

        if ($this->enforceFamilyCondition) {
            $family = $this->attribute->getFamily();
            if (!$family) {
                throw new MissingFamilyException("Unable to resolve family for attribute {$attributeCode}");
            }
            $familyParameter = $this->generateUniqueId('family');
            $qb->setParameter($familyParameter, $family->getCode());
            $joinDql .= " AND {$this->joinAlias}.familyCode = :{$familyParameter}";
        }

        $qb->leftJoin(
            $this->eavQueryBuilder->getAlias().'.values',
            $this->joinAlias,
            Join::WITH,
            $joinDql
        );

        if ($this->joinRelation) {
            $qb->leftJoin(
                $this->joinAlias.'.'.$this->attribute->getType()->getDatabaseType(),
                $this->joinRelation
            );
        }

        $this->joinApplied = true;

        return $this;
    }

    /**
     * Cloning an attribute query builder can be used to allow multiple conditions to be applied to a same join
     */
    public function __clone()
    {
        $this->dql = null;
        $this->skipJoin = true;
    }

    /**
     * @param string $operator
     * @param mixed  $parameter
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
     * Prepare the join alias for later
     */
    protected function prepareJoin()
    {
        $this->joinAlias = $this->generateUniqueId('join');
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
