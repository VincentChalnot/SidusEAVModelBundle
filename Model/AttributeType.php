<?php

namespace Sidus\EAVModelBundle\Model;

class AttributeType implements AttributeTypeInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $databaseType;

    /** @var string */
    protected $formType;

    /** @var bool */
    protected $isEmbedded = false;

    /** @var array */
    protected $formOptions = [];

    /**
     * AttributeType constructor.
     * @param string $code
     * @param string $databaseType
     * @param string $formType
     * @param array $formOptions
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
     * @return boolean
     */
    public function isEmbedded()
    {
        return $this->isEmbedded;
    }

    /**
     * @param boolean $isEmbedded
     */
    public function setIsEmbedded($isEmbedded)
    {
        $this->isEmbedded = $isEmbedded;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
    }

    /**
     * @param $data
     * @return array
     */
    public function getFormOptions($data)
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
}
