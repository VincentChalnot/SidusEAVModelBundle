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
interface EAVQueryBuilderInterface
{
    /**
     * @return FamilyInterface
     */
    public function getFamily();

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param string $attributeCode
     *
     * @return AttributeQueryBuilderInterface
     */
    public function attributeByCode($attributeCode);

    /**
     * @param AttributeInterface $attribute
     *
     * @return AttributeQueryBuilderInterface
     */
    public function attribute(AttributeInterface $attribute);

    /**
     * @param array $eavQueryBuilders
     *
     * @return EAVQueryBuilderInterface
     */
    public function getAnd(array $eavQueryBuilders);

    /**
     * @param array $eavQueryBuilders
     *
     * @return EAVQueryBuilderInterface
     */
    public function getOr(array $eavQueryBuilders);

    /**
     * @return string
     */
    public function getAlias();

    /**
     * @param DQLHandler $DQLHandler
     *
     * @return DQLHandler
     */
    public function apply(DQLHandler $DQLHandler);
}
