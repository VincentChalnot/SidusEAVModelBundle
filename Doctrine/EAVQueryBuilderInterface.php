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
     * @param AttributeQueryBuilderInterface $attributeQueryBuilder
     * @param string                         $direction
     *
     * @return EAVQueryBuilder
     */
    public function addOrderBy(AttributeQueryBuilderInterface $attributeQueryBuilder, $direction = null);

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

    /**
     * @param array|null $context
     */
    public function setContext(array $context = null);
}
