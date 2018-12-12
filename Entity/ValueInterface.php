<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * Must return the actual scalar/relation hold by this value
     *
     * @return mixed
     */
    public function getValueData();

    /**
     * @param mixed $valueData
     *
     * @return mixed
     */
    public function setValueData($valueData);

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
