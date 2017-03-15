<?php

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
     * Set attributeCode : Warning ! No check here to whether or not the attribute actually exists
     *
     * @param string $attributeCode
     *
     * @return ValueInterface
     */
    public function setAttributeCode($attributeCode);

    /**
     * @return AttributeInterface
     */
    public function getAttribute();

    /**
     * @param AttributeInterface $attribute
     *
     * @return ValueInterface
     */
    public function setAttribute(AttributeInterface $attribute);

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
