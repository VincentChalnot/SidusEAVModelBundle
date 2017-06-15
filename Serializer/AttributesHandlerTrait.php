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

namespace Sidus\EAVModelBundle\Serializer;

/**
 * Used to specify ignored attributes and reference attributes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
trait AttributesHandlerTrait
{
    /** @var array */
    protected $ignoredAttributes;

    /** @var array */
    protected $referenceAttributes;

    /**
     * Set ignored attributes for normalization and denormalization.
     *
     * @param array $ignoredAttributes
     */
    public function setIgnoredAttributes(array $ignoredAttributes)
    {
        $this->ignoredAttributes = $ignoredAttributes;
    }

    /**
     * @param array $ignoredAttributes
     */
    public function addIgnoredAttributes(array $ignoredAttributes)
    {
        $this->ignoredAttributes = array_merge($this->ignoredAttributes, $ignoredAttributes);
    }

    /**
     * Set attributes used to normalize a data by reference
     *
     * @param array $referenceAttributes
     */
    public function setReferenceAttributes(array $referenceAttributes)
    {
        $this->referenceAttributes = $referenceAttributes;
    }
}
