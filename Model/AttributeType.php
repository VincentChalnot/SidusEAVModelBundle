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

    /** @var string */
    protected $formType;

    /** @var array */
    protected $formOptions = [];

    /**
     * AttributeType constructor.
     *
     * @param string $code
     * @param string $databaseType
     * @param string $formType
     * @param array  $formOptions
     */
    public function __construct($code, $databaseType, $formType, array $formOptions = [])
    {
        $this->code = $code;
        $this->databaseType = $databaseType;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
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
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return array
     */
    public function getFormOptions(AttributeInterface $attribute)
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     */
    public function setFormOptions($formOptions)
    {
        $this->formOptions = $formOptions;
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
