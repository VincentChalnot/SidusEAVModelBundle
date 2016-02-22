<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Model\AttributeInterface;

abstract class Value
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Data
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\Data", inversedBy="values", fetch="EAGER")
     * @ORM\JoinColumn(name="data_id", referencedColumnName="id", onDelete="cascade", nullable=false)
     */
    protected $data;

    /**
     * @var Data
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\Data", cascade={"persist"})
     * @ORM\JoinColumn(name="data_value_id", referencedColumnName="id", onDelete="cascade", nullable=true)
     */
    protected $dataValue;

    /**
     * @var string
     * @ORM\Column(name="attribute_code", type="string", length=255)
     */
    protected $attributeCode;

    /**
     * @var integer
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    protected $position;

    /**
     * @var Context
     * @ORM\OneToOne(targetEntity="Sidus\EAVModelBundle\Entity\Context", cascade={"persist"}, mappedBy="value", fetch="EAGER")
     */
    protected $context;

    /**
     * @var boolean
     * @ORM\Column(name="bool_value", type="boolean", nullable=true)
     */
    protected $boolValue;

    /**
     * @var integer
     * @ORM\Column(name="integer_value", type="integer", nullable=true)
     */
    protected $integerValue;

    /**
     * @var float
     * @ORM\Column(name="decimal_value", type="float", nullable=true)
     */
    protected $decimalValue;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_value", type="date", nullable=true)
     */
    protected $dateValue;

    /**
     * @var \DateTime
     * @ORM\Column(name="datetime_value", type="datetime", nullable=true)
     */
    protected $datetimeValue;

    /**
     * @var string
     * @ORM\Column(name="string_value", type="string", length=255, nullable=true)
     */
    protected $stringValue;

    /**
     * @var string
     * @ORM\Column(name="text_value", type="text", nullable=true)
     */
    protected $textValue;

    /**
     * @param Data $data
     * @param AttributeInterface $attribute
     */
    public function __construct(Data $data = null, AttributeInterface $attribute = null)
    {
        $this->data = $data;
        if ($attribute) {
            $this->attributeCode = $attribute->getCode();
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set attributeCode
     *
     * @param string $attributeCode
     * @return Value
     */
    public function setAttributeCode($attributeCode)
    {
        $this->attributeCode = $attributeCode;
        return $this;
    }

    /**
     * Get attributeCode
     *
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * Set boolValue
     *
     * @param boolean $boolValue
     * @return Value
     */
    public function setBoolValue($boolValue)
    {
        $this->boolValue = $boolValue;
        return $this;
    }

    /**
     * Get boolValue
     *
     * @return boolean
     */
    public function getBoolValue()
    {
        return $this->boolValue;
    }

    /**
     * Set integerValue
     *
     * @param integer $integerValue
     * @return Value
     */
    public function setIntegerValue($integerValue)
    {
        $this->integerValue = $integerValue;
        return $this;
    }

    /**
     * Get integerValue
     *
     * @return integer
     */
    public function getIntegerValue()
    {
        return $this->integerValue;
    }

    /**
     * Set decimalValue
     *
     * @param float $decimalValue
     * @return Value
     */
    public function setDecimalValue($decimalValue)
    {
        $this->decimalValue = $decimalValue;
        return $this;
    }

    /**
     * Get decimalValue
     *
     * @return float
     */
    public function getDecimalValue()
    {
        return $this->decimalValue;
    }

    /**
     * Set dateValue
     *
     * @param \DateTime $dateValue
     * @return Value
     */
    public function setDateValue(\DateTime $dateValue = null)
    {
        $this->dateValue = $dateValue;
        return $this;
    }

    /**
     * Get dateValue
     *
     * @return \DateTime
     */
    public function getDateValue()
    {
        return $this->dateValue;
    }

    /**
     * Set datetimeValue
     *
     * @param \DateTime $datetimeValue
     * @return Value
     */
    public function setDatetimeValue(\DateTime $datetimeValue = null)
    {
        $this->datetimeValue = $datetimeValue;
        return $this;
    }

    /**
     * Get datetimeValue
     *
     * @return \DateTime
     */
    public function getDatetimeValue()
    {
        return $this->datetimeValue;
    }

    /**
     * Set stringValue
     *
     * @param string $stringValue
     * @return Value
     */
    public function setStringValue($stringValue)
    {
        $this->stringValue = $stringValue;

        return $this;
    }

    /**
     * Get stringValue
     *
     * @return string
     */
    public function getStringValue()
    {
        return $this->stringValue;
    }

    /**
     * Set textValue
     *
     * @param string $textValue
     * @return Value
     */
    public function setTextValue($textValue)
    {
        $this->textValue = $textValue;

        return $this;
    }

    /**
     * Get textValue
     *
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }

    /**
     * Set dataValue
     *
     * @param integer $dataValue
     * @return Value
     */
    public function setDataValue($dataValue)
    {
        $this->dataValue = $dataValue;

        return $this;
    }

    /**
     * Get dataValue
     *
     * @return integer
     */
    public function getDataValue()
    {
        return $this->dataValue;
    }

    /**
     * @return Data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Data $data
     */
    public function setData(Data $data = null)
    {
        $this->data = $data;
    }

    /**
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param ContextInterface $context
     */
    public function setContext(ContextInterface $context)
    {
        $context->setValue($this);
        $this->context = $context;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Remove id on clone
     */
    public function __clone() {
        $this->id = null;
    }
}
