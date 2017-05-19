<?php

namespace Sidus\EAVModelBundle\Serializer;


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
