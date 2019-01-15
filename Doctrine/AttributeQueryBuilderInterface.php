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

/**
 * Applies logical conditions on attributes in the EAV Model for the Doctrine Query Builder
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AttributeQueryBuilderInterface extends DQLHandlerInterface
{
    /**
     * @throws \LogicException
     *
     * @return string
     */
    public function getDQL();

    /**
     * @param array $array
     *
     * @return AttributeQueryBuilderInterface
     */
    public function in(array $array);

    /**
     * @param array $array
     *
     * @return AttributeQueryBuilderInterface
     */
    public function notIn(array $array);

    /**
     * @param mixed $scalar
     *
     * @return AttributeQueryBuilderInterface
     */
    public function equals($scalar);

    /**
     * @param mixed $scalar
     *
     * @return AttributeQueryBuilderInterface
     */
    public function notEquals($scalar);

    /**
     * @param mixed $scalar
     *
     * @return AttributeQueryBuilderInterface
     */
    public function like($scalar);

    /**
     * @param mixed $scalar
     *
     * @return AttributeQueryBuilderInterface
     */
    public function notLike($scalar);

    /**
     * @param mixed $number
     *
     * @return AttributeQueryBuilderInterface
     */
    public function gt($number);

    /**
     * @param mixed $number
     *
     * @return AttributeQueryBuilderInterface
     */
    public function gte($number);

    /**
     * @param mixed $number
     *
     * @return AttributeQueryBuilderInterface
     */
    public function lt($number);

    /**
     * @param mixed $number
     *
     * @return AttributeQueryBuilderInterface
     */
    public function lte($number);

    /**
     * @param mixed $lower
     * @param mixed $upper
     *
     * @return AttributeQueryBuilderInterface
     */
    public function between($lower, $upper);

    /**
     * @param mixed $lower
     * @param mixed $upper
     *
     * @return AttributeQueryBuilderInterface
     */
    public function notBetween($lower, $upper);

    /**
     * @return AttributeQueryBuilderInterface
     */
    public function isNull();

    /**
     * @return AttributeQueryBuilderInterface
     */
    public function isNotNull();

    /**
     * @param string $dql
     *
     * @return AttributeQueryBuilderInterface
     */
    public function rawDQL($dql);

    /**
     * Use this attribute to join on a related entity.
     * Returns an EAVQueryBuilder by default but can be used for other Doctrine entities too.
     *
     * @param string $alias
     *
     * @return EAVQueryBuilderInterface
     */
    public function join($alias = null);

    /**
     * @return string
     */
    public function getColumn();

    /**
     * @return AttributeQueryBuilderInterface
     */
    public function applyJoin();

    /**
     * @param array|null $context
     *
     * @throws \Sidus\EAVModelBundle\Exception\ContextException
     *
     * @return AttributeQueryBuilderInterface
     */
    public function setContext(array $context = null);
}
