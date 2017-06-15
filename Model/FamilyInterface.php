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

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;

/**
 * Interface for family services
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface FamilyInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return AttributeInterface|null
     */
    public function getAttributeAsLabel();

    /**
     * @return AttributeInterface|null
     */
    public function getAttributeAsIdentifier();

    /**
     * @return AttributeInterface[]
     */
    public function getAttributes();

    /**
     * @param string $code
     *
     * @throws MissingAttributeException
     *
     * @return AttributeInterface
     */
    public function getAttribute($code);

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasAttribute($code);

    /**
     * @return FamilyInterface
     */
    public function getParent();

    /**
     * @return FamilyInterface[]
     */
    public function getChildren();

    /**
     * @return array
     */
    public function getMatchingCodes();

    /**
     * @return DataInterface
     */
    public function createData();

    /**
     * @param DataInterface      $data
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @return ValueInterface
     */
    public function createValue(DataInterface $data, AttributeInterface $attribute, array $context = null);

    /**
     * @return string
     */
    public function getDataClass();

    /**
     * @return string
     */
    public function getValueClass();

    /**
     * @return bool
     */
    public function isInstantiable();

    /**
     * @return bool
     */
    public function isSingleton();

    /**
     * @return array
     */
    public function getContext();

    /**
     * @return array
     */
    public function getOptions();
}
