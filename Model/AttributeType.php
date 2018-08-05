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
 * Type of attribute like string, integer, etc.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeType implements AttributeTypeInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $databaseType;

    /**
     * @param string $code
     * @param string $databaseType
     */
    public function __construct(string $code, string $databaseType)
    {
        $this->code = $code;
        $this->databaseType = $databaseType;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getDatabaseType()
    {
        return $this->databaseType;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
    }

    /**
     * @return boolean
     */
    public function isEmbedded()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isRelation()
    {
        return false;
    }
}
