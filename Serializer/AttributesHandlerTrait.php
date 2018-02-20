<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
