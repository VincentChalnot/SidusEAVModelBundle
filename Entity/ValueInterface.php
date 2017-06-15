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

namespace Sidus\EAVModelBundle\Entity;

use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Interface for value storage
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ValueInterface
{
    /**
     * A unique way of identifying a value across the all system
     *
     * @return int|string
     */
    public function getIdentifier();

    /**
     * The attribute's code of the value
     *
     * @return string
     */
    public function getAttributeCode();

    /**
     * The family code of the attribute, used for advanced multi-family queries
     *
     * @return string
     */
    public function getFamilyCode();

    /**
     * @return AttributeInterface
     */
    public function getAttribute();

    /**
     * The data carrying the current value
     *
     * @return DataInterface
     */
    public function getData();

    /**
     * The data carrying the current value
     *
     * @param DataInterface $data
     */
    public function setData(DataInterface $data = null);

    /**
     * Position of the value when used in a collection
     *
     * @return int
     */
    public function getPosition();

    /**
     * @param int $position
     */
    public function setPosition($position);
}
