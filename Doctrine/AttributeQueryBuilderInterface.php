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
}
