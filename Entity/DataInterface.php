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

use Doctrine\Common\Collections\Collection;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Exception\InvalidValueDataException;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Interface for data storage
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DataInterface
{
    /**
     * Should it be removed ?
     *
     * @return mixed
     */
    public function getId();

    /**
     * @throws InvalidValueDataException
     *
     * @return mixed
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getFamilyCode();

    /**
     * @return DataInterface|null
     */
    public function getParent();

    /**
     * @param DataInterface $parent
     *
     * @return DataInterface|null
     */
    public function setParent(DataInterface $parent = null);

    /**
     * Used to get values in a simple way
     *
     * @param string $attributeCode
     * @param array  $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return mixed
     */
    public function get($attributeCode, array $context = null);

    /**
     * Used to set values as in a simple way
     *
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function set($attributeCode, $value, array $context = null);

    /**
     * Append a new value to a collection
     *
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     * @throws \LogicException
     *
     * @return DataInterface
     */
    public function add($attributeCode, $value, array $context = null);

    /**
     * Search the value in the collection and remove it
     *
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     * @throws \LogicException
     *
     * @return DataInterface
     */
    public function remove($attributeCode, $value, array $context = null);

    /**
     * Return all values objects matching the attribute code
     *
     * @param AttributeInterface|null $attribute
     * @param array                   $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    public function getValues(AttributeInterface $attribute = null, array $context = null);

    /**
     * Return first value object found for attribute code in value collection
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return null|ValueInterface
     */
    public function getValue(AttributeInterface $attribute, array $context = null);

    /**
     * Get the value data of the value matching the attribute
     *
     * @internal Using this method outside of this class might lead to unexpected behaviors as it won't pass through
     *           your custom getters, use DataInterface::get() instead
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return mixed
     */
    public function getValueData(AttributeInterface $attribute, array $context = null);

    /**
     * Get the values data of multiple values for a given attribute
     *
     * @internal Using this method outside of this class might lead to unexpected behaviors as it won't pass through
     *           your custom getters, use DataInterface::get() instead
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|array
     */
    public function getValuesData(AttributeInterface $attribute, array $context = null);

    /**
     * Set the value's data of a given attribute
     *
     * @internal Using this method outside of this class might lead to unexpected behaviors as it won't pass through
     *           your custom setters, use DataInterface::set() instead
     *
     * @param AttributeInterface $attribute
     * @param mixed              $dataValue
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function setValueData(AttributeInterface $attribute, $dataValue, array $context = null);

    /**
     * Set the data values of a given collection attribute
     *
     * @internal Using this method outside of this class might lead to unexpected behaviors as it won't pass through
     *           your custom setters, use DataInterface::set() instead
     *
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function setValuesData(AttributeInterface $attribute, $dataValues, array $context = null);

    /**
     * Remove all the values associated with the given attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function emptyValues(AttributeInterface $attribute = null, array $context = null);

    /**
     * Append a value to the internal values stack
     *
     * @param ValueInterface $value
     *
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function addValue(ValueInterface $value);

    /**
     * Append data to an attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $valueData
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function addValueData(AttributeInterface $attribute, $valueData, array $context = null);

    /**
     * Removes a specific value from the values stack
     *
     * @param ValueInterface $value
     *
     * @return DataInterface
     */
    public function removeValue(ValueInterface $value);

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    public function getRefererValues(FamilyInterface $family = null, AttributeInterface $attribute = null, array $context = null);

    /**
     * @param FamilyInterface|null    $family
     * @param AttributeInterface|null $attribute
     * @param array                   $context
     *
     * @throws ContextException
     *
     * @return Collection|DataInterface[]
     */
    public function getRefererDatas(FamilyInterface $family = null, AttributeInterface $attribute = null, array $context = null);

    /**
     * Returns the value carried by the attributeAsLabel attribute
     *
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return FamilyInterface
     */
    public function getFamily();

    /**
     * @internal Don't use this method outside of this class
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws MissingAttributeException
     *
     * @return ValueInterface
     */
    public function createValue(AttributeInterface $attribute, array $context = null);

    /**
     * Check if the data has any value corresponding to the given attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return bool
     */
    public function isEmpty(AttributeInterface $attribute, array $context = null);
}
