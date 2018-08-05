<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Model;

/**
 * Interface for attribute types services
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AttributeTypeInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getDatabaseType();

    /**
     * @return bool
     */
    public function isEmbedded();

    /**
     * @return bool
     */
    public function isRelation();

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute);
}
