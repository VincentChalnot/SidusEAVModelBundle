<?php

namespace Sidus\EAVModelBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Build complex doctrine queries with the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface EAVQueryBuilderInterface
{
    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();

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
     * @param DQLHandlerInterface $DQLHandler
     *
     * @return QueryBuilder
     */
    public function apply(DQLHandlerInterface $DQLHandler);
}
