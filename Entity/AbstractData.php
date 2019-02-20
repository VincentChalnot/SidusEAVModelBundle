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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sidus\BaseBundle\Utilities\DebugInfoUtility;
use Sidus\BaseBundle\Utilities\SleepUtility;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Exception\InvalidValueDataException;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\BaseBundle\Utilities\DateTimeUtility;
use Sidus\EAVModelBundle\Validator\Constraints\Data as DataConstraint;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Caster\CutStub;

/**
 * Base logic to handle the EAV data
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @DataConstraint()
 */
abstract class AbstractData implements ContextualDataInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var DataInterface
     *
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="cascade")
     */
    protected $parent;

    /**
     * @var DataInterface[]
     *
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", mappedBy="parent",
     *                                                    cascade={"all"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @var ValueInterface[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\ValueInterface", mappedBy="data",
     *                                                    cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $values;

    /**
     * @var ValueInterface[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\ValueInterface", cascade={"persist"},
     *                                                                           mappedBy="dataValue")
     */
    protected $refererValues;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var FamilyInterface
     *
     * @ORM\Column(name="family_code", type="sidus_family", length=255)
     */
    protected $family;

    /**
     * Current context of the data, not stored in the database, only used at runtime
     *
     * @var array
     */
    protected $currentContext;

    /**
     * Used as an internal cache to access more easily values based on their attribute
     *
     * @var ValueInterface[][]
     */
    protected $valuesByAttributes;

    /**
     * Common property accessor for all methods
     *
     * @var PropertyAccessorInterface
     */
    protected $accessor;

    /**
     * Initialize the data with the mandatory family
     *
     * @param FamilyInterface $family
     *
     * @throws \LogicException
     * @throws InvalidValueDataException
     */
    public function __construct(FamilyInterface $family)
    {
        if (!$family->isInstantiable()) {
            throw new \LogicException("Family {$family->getCode()} is not instantiable");
        }
        $this->family = $family;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->values = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->refererValues = new ArrayCollection();

        foreach ($family->getAttributes() as $attribute) {
            if (null !== $attribute->getDefault()) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                $this->createDefaultValues($attribute);
            }
        }
    }

    /**
     * @throws InvalidValueDataException
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        $identifierAttribute = $this->getFamily()->getAttributeAsIdentifier();
        if ($identifierAttribute) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */

            return $this->get($identifierAttribute->getCode());
        }

        return $this->getId();
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @throws \UnexpectedValueException
     *
     * @return DataInterface
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = DateTimeUtility::parse($createdAt, false);

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @throws \UnexpectedValueException
     *
     * @return DataInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = DateTimeUtility::parse($updatedAt, false);

        return $this;
    }

    /**
     * @return DataInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param DataInterface $parent
     *
     * @return DataInterface
     */
    public function setParent(DataInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Append data to an attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $valueData
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function addValueData(AttributeInterface $attribute, $valueData, array $context = null)
    {
        if (!$attribute->isCollection()) {
            $m = "Cannot append data to a non-collection attribute '{$attribute->getCode()}'";
            throw new InvalidValueDataException($m);
        }
        $newValue = $this->createValue($attribute, $context);
        $position = -1;
        foreach ($this->getInternalValues($attribute, $context) as $value) {
            $position = max($position, $value->getPosition());
        }
        $newValue->setPosition($position + 1);
        $this->setInternalValueData($attribute, $newValue, $valueData);

        return $this;
    }

    /**
     * Remove a data from an attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $valueData
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function removeValueData(AttributeInterface $attribute, $valueData, array $context = null)
    {
        if (!$attribute->isCollection()) {
            $m = "Cannot remove data from a non-collection attribute '{$attribute->getCode()}'";
            throw new InvalidValueDataException($m);
        }
        foreach ($this->getValues($attribute, $context) as $value) {
            try {
                if ($value->getValueData() === $valueData) {
                    $this->removeValue($value);
                    break;
                }
            } catch (ExceptionInterface $e) {
                throw new InvalidValueDataException("Invalid data for attribute {$attribute->getCode()}", 0, $e);
            }
        }

        return $this;
    }

    /**
     * @param array|null $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return string
     */
    public function getLabel(array $context = null)
    {
        if ($this->getFamily()->hasAttribute('label')) {
            return $this->getValueData($this->getAttribute('label'), $context);
        }
        $label = null;
        try {
            $label = $this->getLabelValue($context);
        } catch (\Exception $e) {
        }
        if (null === $label && $this->getIdentifier()) {
            $label = "[{$this->getIdentifier()}]";
        }

        return $label;
    }

    /**
     * Get the value data of the value matching the attribute
     *
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return mixed
     */
    public function getValueData(AttributeInterface $attribute, array $context = null)
    {
        $valuesData = $this->getValuesData($attribute, $context);

        return 0 === \count($valuesData) ? null : $valuesData->first();
    }

    /**
     * Get the values data of multiple values for a given attribute
     *
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return ArrayCollection
     */
    public function getValuesData(AttributeInterface $attribute, array $context = null)
    {
        $valuesData = new ArrayCollection();
        try {
            foreach ($this->getValues($attribute, $context) as $value) {
                $valuesData->add($value->getValueData());
            }
        } catch (ExceptionInterface $e) {
            throw new InvalidValueDataException("Unable to access data for attribute {$attribute->getCode()}", 0, $e);
        }


        return $valuesData;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->getLabelValue();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Magic method to allow direct access to EAV model
     *
     * @param string $methodName
     * @param array  $arguments
     *
     * @throws \BadMethodCallException
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return mixed|null|ValueInterface
     */
    public function __call($methodName, $arguments)
    {
        $class = \get_class($this);

        $context = array_key_exists(0, $arguments) ? $arguments[0] : null;

        // Standard getter
        if (0 === strpos($methodName, 'get')) {
            return $this->get(lcfirst(substr($methodName, 3)), $context);
        }

        $baseErrorMsg = "Method '{$methodName}' for object '{$class}' with family '{$this->getFamilyCode()}'";

        // Test setter, adder and remover
        foreach (['set', 'add', 'remove'] as $action) {
            if (0 === strpos($methodName, $action)) {
                if (!array_key_exists(0, $arguments)) {
                    throw new \BadMethodCallException("{$baseErrorMsg} requires at least one argument");
                }
                $context = array_key_exists(1, $arguments) ? $arguments[1] : null;
                $attributeCode = lcfirst(substr($methodName, \strlen($action)));

                return $this->$action($attributeCode, $arguments[0], $context);
            }
        }

        throw new \BadMethodCallException("{$baseErrorMsg} does not exist");
    }

    /**
     * Used to get values in a simple way
     *
     * @param string     $attributeCode
     * @param array|null $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return mixed
     */
    public function get($attributeCode, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $getter = 'get'.ucfirst($attributeCode);
        if (method_exists($this, $getter)) {
            return $this->$getter($context);
        }

        if ($attribute->isCollection()) {
            return $this->getValuesData($attribute, $context);
        }

        return $this->getValueData($attribute, $context);
    }

    /**
     * @param string $attributeCode
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return mixed
     */
    public function __get($attributeCode)
    {
        return $this->get($attributeCode);
    }

    /**
     * Used to set values as in a simple way
     *
     * @param string     $attributeCode
     * @param mixed      $value
     * @param array|null $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function set($attributeCode, $value, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $method = 'set'.ucfirst($attributeCode);
        if (method_exists($this, $method)) {
            return $this->$method($value, $context);
        }

        if ($attribute->isCollection()) {
            return $this->setValuesData($attribute, $value, $context);
        }

        return $this->setValueData($attribute, $value, $context);
    }

    /**
     * @param string $attributeCode
     * @param mixed  $value
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function __set($attributeCode, $value)
    {
        return $this->set($attributeCode, $value);
    }

    /**
     * @param string $attributeCode
     *
     * @return bool
     */
    public function __isset($attributeCode)
    {
        return $this->getFamily()->hasAttribute($attributeCode);
    }

    /**
     * Append a new value to a collection
     *
     * @param string     $attributeCode
     * @param mixed      $value
     * @param array|null $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function add($attributeCode, $value, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $method = 'add'.ucfirst($attributeCode);
        if (method_exists($this, $method)) {
            return $this->$method($value, $context);
        }

        return $this->addValueData($attribute, $value, $context);
    }

    /**
     * Search the value in the collection and remove it
     *
     * @param string     $attributeCode
     * @param mixed      $value
     * @param array|null $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function remove($attributeCode, $value, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $method = 'remove'.ucfirst($attributeCode);
        if (method_exists($this, $method)) {
            return $this->$method($value, $context);
        }

        return $this->removeValueData($attribute, $value, $context);
    }

    /**
     * Return first value found for attribute code in value collection
     *
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return null|ValueInterface
     */
    public function getValue(AttributeInterface $attribute, array $context = null)
    {
        $values = $this->getValues($attribute, $context);

        return 0 === \count($values) ? null : $values->first();
    }

    /**
     * Return all values matching the attribute code
     *
     * @param AttributeInterface|null $attribute
     * @param array|null              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    public function getValues(AttributeInterface $attribute = null, array $context = null)
    {
        $values = $this->getInternalValues($attribute, $context);

        if ($attribute && 0 === \count($values) && null !== $attribute->getDefault()) {
            return $this->createDefaultValues($attribute, $context);
        }

        return $values;
    }

    /**
     * @param FamilyInterface|null    $family
     * @param AttributeInterface|null $attribute
     * @param array|null              $context
     *
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    public function getRefererValues(
        FamilyInterface $family = null,
        AttributeInterface $attribute = null,
        array $context = null
    ) {
        if (null === $family && null === $attribute && null === $context) {
            return $this->refererValues;
        }
        $values = new ArrayCollection();
        if ($context) {
            $context = array_merge($this->getCurrentContext(), $context);
        }

        foreach ($this->refererValues as $refererValue) {
            if ($attribute && $attribute->getCode() !== $refererValue->getAttributeCode()) {
                continue;
            }
            if ($family && $family->getCode() !== $refererValue->getData()->getFamilyCode()) {
                continue;
            }
            if ($context && !$refererValue->getAttribute()->isContextMatching($refererValue, $context)) {
                continue;
            }
            $values[] = $refererValue;
        }

        return $values;
    }

    /**
     * @param FamilyInterface|null    $family
     * @param AttributeInterface|null $attribute
     * @param array|null              $context
     *
     * @throws ContextException
     *
     * @return Collection|DataInterface[]
     */
    public function getRefererDatas(
        FamilyInterface $family = null,
        AttributeInterface $attribute = null,
        array $context = null
    ) {
        $datas = new ArrayCollection();
        foreach ($this->getRefererValues($family, $attribute, $context) as $refererValue) {
            $data = $refererValue->getData();
            if ($data) {
                $datas[] = $data;
            }
        }

        return $datas;
    }

    /**
     * @return array
     */
    public function getCurrentContext()
    {
        if (!$this->currentContext) {
            return $this->getFamily()->getContext();
        }

        return $this->currentContext;
    }

    /**
     * @param array $currentContext
     */
    public function setCurrentContext(array $currentContext = [])
    {
        $this->currentContext = array_merge($this->getCurrentContext(), $currentContext);
    }

    /**
     * @return FamilyInterface
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @return string
     */
    public function getFamilyCode()
    {
        return $this->getFamily()->getCode();
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws MissingAttributeException
     *
     * @return ValueInterface
     */
    public function createValue(AttributeInterface $attribute, array $context = null)
    {
        $this->checkAttribute($attribute);

        return $this->getFamily()->createValue($this, $attribute, $context);
    }

    /**
     * Set the values' data of a given attribute for multiple fields
     *
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function setValuesData(AttributeInterface $attribute, $dataValues, array $context = null)
    {
        $this->setInternalValuesData($attribute, $dataValues, $context);

        return $this;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function emptyValues(AttributeInterface $attribute = null, array $context = null)
    {
        $values = $this->getInternalValues($attribute, $context);
        foreach ($values as $value) {
            $this->removeValue($value);
        }

        return $this;
    }

    /**
     * @param ValueInterface $value
     *
     * @return DataInterface
     */
    public function removeValue(ValueInterface $value)
    {
        $this->removeValueByAttribute($value);
        $value->setData(null);
        $this->values->removeElement($value);

        return $this;
    }

    /**
     * @param ValueInterface $value
     *
     * @throws ContextException
     * @throws MissingAttributeException
     *
     * @return DataInterface
     */
    public function addValue(ValueInterface $value)
    {
        if ($value instanceof ContextualValueInterface && !$value->getContext()) {
            $value->setContext($this->getCurrentContext());
        }
        $this->getAttribute($value->getAttributeCode()); // Only to check that the attribute does exists

        $this->values->add($value);
        $value->setData($this);

        $this->addValueByAttribute($value);

        return $this;
    }

    /**
     * Set the value's data of a given attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $dataValue
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function setValueData(AttributeInterface $attribute, $dataValue, array $context = null)
    {
        return $this->setValuesData($attribute, [$dataValue], $context);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return bool
     */
    public function isEmpty(AttributeInterface $attribute, array $context = null)
    {
        foreach ($this->getValuesData($attribute, $context) as $valueData) {
            if (null !== $valueData && '' !== $valueData) {
                return false;
            }
        }

        return true;
    }

    /**
     * @internal Be careful when using this method in your code, it will return the raw values collection without any
     *           filter on attributes or context.
     *
     * @return Collection|ValueInterface[]
     */
    public function getValuesCollection()
    {
        return $this->values;
    }

    /**
     * Remove id on clone and clean values
     *
     * @throws InvalidValueDataException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     */
    public function __clone()
    {
        $this->id = null;
        $this->valuesByAttributes = null; // Cleaning internal "cache"

        // Cloning the previous values
        $newValues = new ArrayCollection();
        $identifierAttribute = $this->getFamily()->getAttributeAsIdentifier();
        foreach ($this->values as $value) {
            if (!$this->getFamily()->hasAttribute($value->getAttributeCode())) {
                continue; // Purging attributes that does not exists anymore in the model
            }
            if ($identifierAttribute && $identifierAttribute->getCode() === $value->getAttributeCode()) {
                continue; // Skipping identifier attribute
            }
            $newValues[] = clone $value;
        }

        // Calling the constructor manually to reset everything
        /** @noinspection ImplicitMagicMethodCallInspection */
        $this->__construct($this->getFamily());

        foreach ($newValues as $newValue) {
            $this->values->add($newValue);
            $newValue->setData($this);
        }
    }

    /**
     * Cleaning object before serialization
     *
     * @return array
     */
    public function __sleep()
    {
        return SleepUtility::sleep(__CLASS__, ['accessor', 'valuesByAttributes']);
    }

    /**
     * Custom debugInfo to prevent profiler from crashing
     *
     * @throws \Sidus\EAVModelBundle\Exception\EAVExceptionInterface
     *
     * @return array
     */
    public function __debugInfo()
    {
        $a = DebugInfoUtility::debugInfo(
            $this,
            [
                Caster::PREFIX_PROTECTED.'children',
                Caster::PREFIX_PROTECTED.'values',
                Caster::PREFIX_PROTECTED.'refererValues',
                Caster::PREFIX_PROTECTED.'valuesByAttributes',
                Caster::PREFIX_PROTECTED.'family',
                Caster::PREFIX_PROTECTED.'accessor',
            ]
        );

        $a['identifier'] = $this->getIdentifier();
        $a['label'] = $this->getLabel();
        $a['family'] = $this->getFamilyCode();

        $family = $this->getFamily();
        foreach ($family->getAttributes() as $attribute) {
            $attributeType = $attribute->getType();
            $attributeCode = $attribute->getCode();
            if ($attributeType->isRelation() && !$attribute->getOption('autoload', false)) {
                $a[$attributeCode] = new CutStub($this->get($attributeCode));
            } else {
                $a[$attributeCode] = $this->get($attributeCode);
            }
        }

        return $a;
    }

    /**
     * @param string $attributeCode
     *
     * @throws MissingAttributeException
     *
     * @return AttributeInterface
     */
    protected function getAttribute($attributeCode)
    {
        return $this->getFamily()->getAttribute($attributeCode);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    protected function createDefaultValues(AttributeInterface $attribute, array $context = null)
    {
        $default = $attribute->getDefault();
        if (!$attribute->isCollection()) {
            $default = (array) $default;
        }

        return $this->setInternalValuesData($attribute, $default, $context);
    }

    /**
     * @param AttributeInterface|null $attribute
     * @param array|null              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    protected function getInternalValues(AttributeInterface $attribute = null, array $context = null)
    {
        if ($context) {
            $context = array_merge($this->getCurrentContext(), $context);
        } else {
            $context = $this->getCurrentContext();
        }

        if (null === $attribute) {
            return $this->getAllInternalValues($context);
        }
        $this->checkAttribute($attribute);

        $values = new ArrayCollection();
        foreach ($this->getValuesByAttribute($attribute) as $value) {
            if ($value instanceof ContextualValueInterface) {
                if ($attribute->isContextMatching($value, $context)) {
                    $values->add($value);
                }
            } else {
                $values->add($value);
            }
        }

        return $values;
    }

    /**
     * @param array|null $context
     *
     * @return Collection|ValueInterface[]
     */
    protected function getAllInternalValues(array $context = null)
    {
        $values = new ArrayCollection();
        foreach ($this->values as $value) {
            if (!$this->getFamily()->hasAttribute($value->getAttributeCode())) {
                $this->removeValue($value);
                continue;
            }
            $attribute = $this->getFamily()->getAttribute($value->getAttributeCode());
            if ($attribute->isContextMatching($value, $context)) {
                $values->add($value);
            }
        }

        return $values;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    protected function setInternalValuesData(AttributeInterface $attribute, $dataValues, array $context = null)
    {
        $dataValues = $this->ensureNativeArray($dataValues, $this->getFamilyCode().'.'.$attribute->getCode());
        $values = new ArrayCollection();
        $position = 0; // Reset position to zero

        foreach ($this->getInternalValues($attribute, $context) as $value) {
            // If there values to replace
            if (\count($dataValues)) {
                // Extract new values and replaces them one by one
                $dataValue = array_shift($dataValues);
                $value->setPosition(++$position);
                $this->setInternalValueData($attribute, $value, $dataValue);
                $values->add($value);
            } else {
                // If there are too much existing values previously, remove the extra ones
                $this->removeValue($value);
            }
        }

        // If there are still values to add
        foreach ($dataValues as $dataValue) {
            $value = $this->createValue($attribute, $context);
            $value->setPosition(++$position);
            $this->setInternalValueData($attribute, $value, $dataValue);
            $values->add($value);
        }

        return $values;
    }

    /**
     * Sets the value's data
     *
     * @param AttributeInterface $attribute
     * @param ValueInterface     $value
     * @param mixed              $dataValue
     *
     * @throws InvalidValueDataException
     */
    protected function setInternalValueData(
        AttributeInterface $attribute,
        ValueInterface $value,
        $dataValue
    ) {
        $value->setValueData($dataValue);
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @throws MissingAttributeException
     */
    protected function checkAttribute(AttributeInterface $attribute)
    {
        if (!$this->getFamily()->hasAttribute($attribute->getCode())) {
            throw new MissingAttributeException(
                "Attribute {$attribute->getCode()} doesn't exists in family {$this->getFamilyCode()}"
            );
        }
        if ($attribute->getFamily() !== $this->getFamily()) {
            $m = "Attribute {$attribute->getCode()} (from family '{$attribute->getFamily()->getCode()}') doesn't ";
            $m .= "belong to this family ('{$this->getFamilyCode()}')";
            throw new MissingAttributeException($m);
        }
    }

    /**
     * @param array|null $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return string
     */
    protected function getLabelValue(array $context = null)
    {
        $attributeAsLabel = $this->getFamily()->getAttributeAsLabel();
        if ($attributeAsLabel) {
            return (string) $this->getValueData($attributeAsLabel, $context);
        }

        return (string) $this->getIdentifier();
    }

    /**
     * Cache internally the values indexed by their attribute codes to increase performances
     *
     * @param AttributeInterface $attribute
     *
     * @return ValueInterface[]
     */
    protected function getValuesByAttribute(AttributeInterface $attribute)
    {
        $this->initializeValuesByAttribute();
        if (!array_key_exists($attribute->getCode(), $this->valuesByAttributes)) {
            return [];
        }

        return $this->valuesByAttributes[$attribute->getCode()];
    }

    /**
     * Initialize the internal cache of values indexed by attributes for faster search
     */
    protected function initializeValuesByAttribute()
    {
        if (null === $this->valuesByAttributes) {
            $this->valuesByAttributes = [];
            // Build internal values by attribute cache
            foreach ($this->values as $value) {
                $this->addValueByAttribute($value);
            }
        }
    }

    /**
     * @param ValueInterface $value
     */
    protected function addValueByAttribute(ValueInterface $value)
    {
        $this->initializeValuesByAttribute();

        $key = spl_object_hash($value);
        $this->valuesByAttributes[$value->getAttributeCode()][$key] = $value;
    }

    /**
     * @param ValueInterface $value
     */
    protected function removeValueByAttribute(ValueInterface $value)
    {
        if (null === $this->valuesByAttributes
            || !array_key_exists($value->getAttributeCode(), $this->valuesByAttributes)) {
            return; // No cache so no need to do anything
        }

        $key = spl_object_hash($value);
        unset($this->valuesByAttributes[$value->getAttributeCode()][$key]);
    }

    /**
     * @return PropertyAccessorInterface
     */
    protected function getAccessor()
    {
        if (!$this->accessor) {
            // Instantiate common property accessor
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * @deprecated Use ensureNativeArray
     *
     * @param AttributeInterface $attribute
     * @param \Traversable|array $dataValues
     *
     * @throws InvalidValueDataException
     *
     * @return array
     */
    protected function parseArray(AttributeInterface $attribute, $dataValues)
    {
        $m = 'AbstractData::parseArray is deprecated and will be removed in a future version, ';
        $m .= 'use ensureNativeArray instead';
        @trigger_error($m, E_USER_DEPRECATED);

        return $this->ensureNativeArray($dataValues, $this->getFamilyCode().'.'.$attribute->getCode());
    }

    /**
     * @param array|\Traversable $dataValues
     * @param string             $path
     *
     * @throws \Sidus\EAVModelBundle\Exception\InvalidValueDataException
     *
     * @return array
     */
    protected function ensureNativeArray($dataValues, $path)
    {
        if (\is_array($dataValues)) {
            return $dataValues;
        }

        // Converting traversable to standard array
        if ($dataValues instanceof \Traversable) {
            $arrayDataValues = [];
            foreach ($dataValues as $key => $dataValue) {
                $arrayDataValues[$key] = $dataValue;
            }

            return $arrayDataValues;
        }

        $type = \is_object($dataValues) ? \get_class($dataValues) : \gettype($dataValues);
        throw new InvalidValueDataException(
            "Value for collection for path '{$path}' must be an array, '{$type}' given"
        );
    }
}
