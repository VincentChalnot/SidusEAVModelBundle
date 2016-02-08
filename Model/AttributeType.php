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

    public function __construct($code, $databaseType, $formType)
    {
        $this->code = $code;
        $this->databaseType = $databaseType;
        $this->formType = $formType;
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
}
