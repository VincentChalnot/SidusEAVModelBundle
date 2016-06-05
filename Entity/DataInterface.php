<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\Common\Collections\Collection;
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
     * @return mixed
     */
    public function getId();

    /**
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
     * @return DataInterface|null
     */
    public function setParent(DataInterface $parent = null);

    /**
     * Used to get values in a simple way
     *
     * @param string $attributeCode
     * @param array  $context
     * @return mixed
     */
    public function get($attributeCode, array $context = null);

    /**
     * Used to set values as in a simple way
     *
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
     * @return DataInterface
     */
    public function set($attributeCode, $value, array $context = null);

    /**
     * Return all values matching the attribute code
     *
     * @param AttributeInterface|null $attribute
     * @param array                   $context
     * @return Collection|ValueInterface[]
     */
    public function getValues(AttributeInterface $attribute = null, array $context = null);

    /**
     * Return first value found for attribute code in value collection
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return null|ValueInterface
     */
    public function getValue(AttributeInterface $attribute, array $context = null);

    /**
     * Get the value data of the value matching the attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return mixed
     */
    public function getValueData(AttributeInterface $attribute, array $context = null);

    /**
     * Get the values data of multiple values for a given attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return mixed
     */
    public function getValuesData(AttributeInterface $attribute = null, array $context = null);

    /**
     * Set the value's data of a given attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $dataValue
     * @param array              $context
     * @return DataInterface
     */
    public function setValueData(AttributeInterface $attribute, $dataValue, array $context = null);

    /**
     * Set the values' data of a given attribute for multiple fields
     *
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array              $context
     * @return DataInterface
     */
    public function setValuesData(AttributeInterface $attribute, $dataValues, array $context = null);

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return DataInterface
     */
    public function emptyValues(AttributeInterface $attribute = null, array $context = null);

    /**
     * @param ValueInterface $value
     * @return DataInterface
     */
    public function addValue(ValueInterface $value);

    /**
     * Append data to an attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $valueData
     * @param array              $context
     * @return DataInterface
     */
    public function addValueData(AttributeInterface $attribute, $valueData, array $context = null);

    /**
     * @param ValueInterface $value
     * @return DataInterface
     */
    public function removeValue(ValueInterface $value);

    /**
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
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return ValueInterface
     */
    public function createValue(AttributeInterface $attribute, array $context = null);

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return bool
     */
    public function isEmpty(AttributeInterface $attribute, array $context = null);
}
