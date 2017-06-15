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

namespace Sidus\EAVModelBundle\Registry;

use Sidus\EAVModelBundle\Exception\MissingAttributeTypeException;
use Sidus\EAVModelBundle\Model\AttributeTypeInterface;
use UnexpectedValueException;

/**
 * Container for all attribute types
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeTypeRegistry
{
    /** @var AttributeTypeInterface[] */
    protected $types;

    /**
     * @param AttributeTypeInterface $type
     */
    public function addType(AttributeTypeInterface $type)
    {
        $this->types[$type->getCode()] = $type;
    }

    /**
     * @return AttributeTypeInterface[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param string $code
     *
     * @return AttributeTypeInterface
     * @throws UnexpectedValueException
     */
    public function getType($code)
    {
        if (!$this->hasType($code)) {
            throw new MissingAttributeTypeException($code);
        }

        return $this->types[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasType($code)
    {
        return array_key_exists($code, $this->types);
    }
}
