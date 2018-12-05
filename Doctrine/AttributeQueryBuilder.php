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

use Doctrine\ORM\Query\Expr\Join;
use Sidus\EAVModelBundle\Entity\ContextualValueInterface;
use Sidus\EAVModelBundle\Exception\ContextException;
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

    /** @var array|null */
    protected $context;

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
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \LogicException
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

        $joinedEAVQb = new EAVQueryBuilder($this->eavQueryBuilder->getQueryBuilder(), $alias);
        $joinedEAVQb->setContext($this->context);

        return $joinedEAVQb;
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
     * @throws \Sidus\EAVModelBundle\Exception\ContextException
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

        if ($this->context) {
            /** @var ContextualValueInterface $valueClass */
            $valueClass = $this->attribute->getFamily()->getValueClass();
            if (!is_a($valueClass, ContextualValueInterface::class, true)) {
                throw new ContextException('Unable to filter by context on a non-contextual value class');
            }
            foreach ($this->context as $axis => $axisValues) {
                if (!\in_array($axis, $valueClass::getContextKeys(), true)) {
                    throw new ContextException("Trying to filter on invalid axis '{$axis}'");
                }
                if (!\in_array($axis, $this->attribute->getContextMask(), true)) {
                    continue;
                }
                $axisValueParameter = $this->generateUniqueId($axis.'Axis');
                $joinDql .= " AND {$this->joinAlias}.{$axis}";
                if (is_array($axisValues)) {
                    $joinDql .= " IN (:{$axisValueParameter})";
                } else {
                    $joinDql .= " = :{$axisValueParameter}";
                }
                $qb->setParameter($axisValueParameter, $axisValues);
            }
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
     * Warning: this method does not enforce the presence of all context axis
     * It's completely fine to skip some axis if you don't want to search in those but beware of this behavior
     *
     * @param array|null $context
     */
    public function setContext(array $context = null)
    {
        $this->context = $context;
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
     *
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
