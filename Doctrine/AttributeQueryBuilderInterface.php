<?php

namespace Sidus\EAVModelBundle\Doctrine;

/**
 * Applies logical conditions on attributes in the EAV Model for the Doctrine Query Builder
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AttributeQueryBuilderInterface extends DQLHandlerInterface
{
    /**
     * @return string
     * @throws \LogicException
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
     * @param string $dql
     *
     * @return AttributeQueryBuilderInterface
     */
    public function rawDQL($dql);
}
