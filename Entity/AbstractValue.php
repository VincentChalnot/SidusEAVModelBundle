<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\BaseBundle\Utilities\DateTimeUtility;

/**
 * Base class for storing values in the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractValue implements ContextualValueInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var DataInterface
     *
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", inversedBy="values", fetch="EAGER")
     * @ORM\JoinColumn(name="data_id", referencedColumnName="id", onDelete="cascade")
     */
    protected $data;

    /**
     * @var DataInterface
     *
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", inversedBy="refererValues",
     *                                                                 fetch="EAGER", cascade={"persist", "detach"})
     * @ORM\JoinColumn(name="data_value_id", referencedColumnName="id", onDelete="cascade", nullable=true)
     */
    protected $dataValue;

    /**
     * Same as dataValue but without the onDelete="cascade"
     *
     * @var DataInterface
     *
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(name="constrained_data_value_id", referencedColumnName="id", nullable=true)
     */
    protected $constrainedDataValue;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_code", type="string", length=255)
     */
    protected $attributeCode;

    /**
     * Used for advanced multi-family queries
     *
     * @var string
     *
     * @ORM\Column(name="family_code", type="string", length=255)
     */
    protected $familyCode;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    protected $position;

    /**
     * @var bool
     *
     * @ORM\Column(name="bool_value", type="boolean", nullable=true)
     */
    protected $boolValue;

    /**
     * @var int
     *
     * @ORM\Column(name="integer_value", type="integer", nullable=true)
     */
    protected $integerValue;

    /**
     * @var float
     *
     * @ORM\Column(name="decimal_value", type="float", nullable=true)
     */
    protected $decimalValue;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_value", type="date", nullable=true)
     */
    protected $dateValue;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="datetime_value", type="datetime", nullable=true)
     */
    protected $datetimeValue;

    /**
     * @var string
     *
     * @ORM\Column(name="string_value", type="string", length=255, nullable=true)
     */
    protected $stringValue;

    /**
     * @var string
     *
     * @ORM\Column(name="text_value", type="text", nullable=true)
     */
    protected $textValue;

    /**
     * @param DataInterface $data
     * @param AttributeInterface $attribute
     *
     * @throws \LogicException
     */
    public function __construct(DataInterface $data, AttributeInterface $attribute)
    {
        if (null === $attribute->getFamily()) {
            throw new \LogicException("Attribute '{$attribute->getCode()}' does not have a configured family");
        }
        $this->data = $data;
        $this->attributeCode = $attribute->getCode();
        $this->familyCode = $attribute->getFamily()->getCode();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->attributeCode;
    }

    /**
     * @return string
     */
    public function getFamilyCode()
    {
        return $this->familyCode;
    }

    /**
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     *
     * @return AttributeInterface
     */
    public function getAttribute()
    {
        if (!$this->getData()) {
            return null;
        }

        return $this->getData()->getFamily()->getAttribute($this->getAttributeCode());
    }

    /**
     * @return boolean
     */
    public function getBoolValue()
    {
        return $this->boolValue;
    }

    /**
     * @param boolean|mixed $boolValue
     *
     * @return AbstractValue
     */
    public function setBoolValue($boolValue)
    {
        if (\is_array($boolValue) || \is_object($boolValue)) {
            $m = "Invalid value type for attribute {$this->getFamilyCode()}.{$this->getAttributeCode()}, ";
            $m .= 'expecting boolean, got'.gettype($boolValue);
            throw new \UnexpectedValueException($m);
        }
        $this->boolValue = null === $boolValue ? null : (bool) $boolValue;

        return $this;
    }

    /**
     * @return integer
     */
    public function getIntegerValue()
    {
        return $this->integerValue;
    }

    /**
     * @param integer|mixed $integerValue
     *
     * @return AbstractValue
     */
    public function setIntegerValue($integerValue)
    {
        if (\is_array($integerValue) || \is_object($integerValue)) {
            $m = "Invalid value type for attribute {$this->getFamilyCode()}.{$this->getAttributeCode()}, ";
            $m .= 'expecting integer, got '.gettype($integerValue);
            throw new \UnexpectedValueException($m);
        }
        $this->integerValue = null === $integerValue ? null : (int) $integerValue;

        return $this;
    }

    /**
     * @return float
     */
    public function getDecimalValue()
    {
        return $this->decimalValue;
    }

    /**
     * @param float|mixed $decimalValue
     *
     * @return AbstractValue
     */
    public function setDecimalValue($decimalValue)
    {
        if (\is_array($decimalValue) || \is_object($decimalValue)) {
            $m = "Invalid value type for attribute {$this->getFamilyCode()}.{$this->getAttributeCode()}, ";
            $m .= 'expecting float, got '.gettype($decimalValue);
            throw new \UnexpectedValueException($m);
        }
        $this->decimalValue = null === $decimalValue ? null : (float) $decimalValue;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateValue()
    {
        return $this->dateValue;
    }

    /**
     * @param DateTime|int|string $dateValue
     *
     * @throws \UnexpectedValueException
     *
     * @return AbstractValue
     */
    public function setDateValue($dateValue)
    {
        $this->dateValue = DateTimeUtility::parse($dateValue);

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDatetimeValue()
    {
        return $this->datetimeValue;
    }

    /**
     * @param DateTime|int|string $datetimeValue
     *
     * @throws \UnexpectedValueException
     *
     * @return AbstractValue
     */
    public function setDatetimeValue($datetimeValue)
    {
        $this->datetimeValue = DateTimeUtility::parse($datetimeValue);

        return $this;
    }

    /**
     * @return string
     */
    public function getStringValue()
    {
        return $this->stringValue;
    }

    /**
     * @param string|mixed $stringValue
     *
     * @return AbstractValue
     */
    public function setStringValue($stringValue)
    {
        if (\is_array($stringValue) || \is_object($stringValue)) {
            $m = "Invalid value type for attribute {$this->getFamilyCode()}.{$this->getAttributeCode()}, ";
            $m .= 'expecting string, got '.gettype($stringValue);
            throw new \UnexpectedValueException($m);
        }
        if (null !== $stringValue && 255 < \mb_strlen($stringValue)) {
            $stringValue = mb_substr($stringValue, 0, 255);
        }
        $this->stringValue = null === $stringValue ? null : (string) $stringValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }

    /**
     * @param string|mixed $textValue
     *
     * @return AbstractValue
     */
    public function setTextValue($textValue)
    {
        if (\is_array($textValue) || \is_object($textValue)) {
            $m = "Invalid value type for attribute {$this->getFamilyCode()}.{$this->getAttributeCode()}, ";
            $m .= 'expecting string, got '.gettype($textValue);
            throw new \UnexpectedValueException($m);
        }
        $this->textValue = null === $textValue ? null : (string) $textValue;

        return $this;
    }

    /**
     * @return DataInterface
     */
    public function getDataValue()
    {
        return $this->dataValue;
    }

    /**
     * @param DataInterface $dataValue
     *
     * @return AbstractValue
     */
    public function setDataValue(DataInterface $dataValue = null)
    {
        $this->dataValue = $dataValue;

        return $this;
    }

    /**
     * @return DataInterface
     */
    public function getConstrainedDataValue()
    {
        return $this->constrainedDataValue;
    }

    /**
     * @param DataInterface $constrainedDataValue
     *
     * @return AbstractValue
     */
    public function setConstrainedDataValue(DataInterface $constrainedDataValue = null)
    {
        $this->constrainedDataValue = $constrainedDataValue;

        return $this;
    }

    /**
     * @return DataInterface
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param DataInterface $data
     */
    public function setData(DataInterface $data = null)
    {
        $this->data = $data;
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
     * @return array
     */
    public function getContext()
    {
        $context = [];
        foreach ($this::getContextKeys() as $key) {
            $context[$key] = $this->$key;
        }

        return $context;
    }

    /**
     * @return array
     */
    public static function getContextKeys()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function checkContext(array $context)
    {
        $missingKeys = array_diff(static::getContextKeys(), array_keys($context));
        if (0 !== \count($missingKeys)) {
            $flattenedContext = implode(', ', $missingKeys);
            throw new ContextException("Missing key(s) in context: {$flattenedContext}");
        }
        $extraKeys = array_diff(array_keys($context), static::getContextKeys());
        if (0 !== \count($extraKeys)) {
            $flattenedContext = implode(', ', $extraKeys);
            throw new ContextException("Extra key(s) in context: {$flattenedContext}");
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws ContextException
     */
    public function getContextValue($key)
    {
        $this->checkContextKey($key);

        return $this->$key;
    }

    /**
     * @param array $context
     *
     * @throws ContextException
     */
    public function setContext(array $context)
    {
        $this->clearContext();
        foreach ($context as $key => $value) {
            $this->setContextValue($key, $value);
        }
    }

    /**
     * Clean all contextual keys
     */
    public function clearContext()
    {
        foreach ($this::getContextKeys() as $key) {
            $this->$key = null;
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws ContextException
     */
    public function setContextValue($key, $value)
    {
        $this->checkContextKey($key);
        $this->$key = $value;
    }

    /**
     * Remove id on clone and clone embedded data
     *
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     */
    public function __clone()
    {
        $this->id = null;
        $attribute = $this->getAttribute();
        if ($this->dataValue && $attribute->getType()->isEmbedded()) {
            $this->dataValue = clone $this->dataValue;
        }
    }

    /**
     * @param string $key
     *
     * @throws ContextException
     */
    protected function checkContextKey($key)
    {
        if (!\in_array($key, $this::getContextKeys(), true)) {
            throw new ContextException("Trying to get a non-allowed context key {$key}");
        }
    }
}
