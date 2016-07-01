<?php

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Build complex doctrine queries with the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVQueryBuilder implements EAVQueryBuilderInterface
{
    /** @var FamilyInterface */
    protected $family;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var string */
    protected $alias;

    /** @var bool */
    protected $isApplied = false;

    /**
     * @param FamilyInterface $family
     * @param QueryBuilder    $queryBuilder
     * @param string          $alias
     */
    public function __construct(FamilyInterface $family, QueryBuilder $queryBuilder, $alias = 'e')
    {
        $this->family = $family;
        $this->queryBuilder = $queryBuilder;
        $this->alias = $alias;
    }

    /**
     * @return FamilyInterface
     */
    public function getFamily()
    {
        return $this->family;
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
     * @param string $attributeCode
     *
     * @return AttributeQueryBuilderInterface
     */
    public function attributeByCode($attributeCode)
    {
        $attribute = $this->getFamily()->getAttribute($attributeCode);

        return $this->attribute($attribute);
    }

    /**
     * @param string $attributeCode
     *
     * @return AttributeQueryBuilderInterface
     */
    public function a($attributeCode)
    {
        return $this->attributeByCode($attributeCode);
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return AttributeQueryBuilderInterface
     */
    public function attribute(AttributeInterface $attribute)
    {
        return new AttributeQueryBuilder($this, $attribute);
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
        $this->queryBuilder->addOrderBy($attributeQueryBuilder->getColumn(), $direction);

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

        return $this->getQueryBuilder()
            ->andWhere($DQLHandler->getDQL())
            ->setParameters($DQLHandler->getParameters());
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
            $dqlStatement[] = $dqlHandler->getDQL();

            foreach ($dqlHandler->getParameters() as $key => $value) {
                $parameters[$key] = $value;
            }
        }

        return new DQLHandler(implode($glue, $dqlStatement), $parameters);
    }
}
