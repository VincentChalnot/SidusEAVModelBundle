<?php

namespace Sidus\EAVModelBundle\Entity;

/**
 * Interface for value storage
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ValueInterface
{
    /**
     * Get attributeCode
     *
     * @return string
     */
    public function getAttributeCode();

    /**
     * Set attributeCode
     *
     * @param string $attributeCode
     * @return Value
     */
    public function setAttributeCode($attributeCode);

    /**
     * @return DataInterface
     */
    public function getData();

    /**
     * @param DataInterface $data
     */
    public function setData(DataInterface $data = null);

    /**
     * @return int
     */
    public function getPosition();

    /**
     * @param int $position
     */
    public function setPosition($position);
}
