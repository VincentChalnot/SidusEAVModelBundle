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

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Exception\InvalidValueDataException;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Utilities\DateTimeUtility;
use Sidus\EAVModelBundle\Validator\Constraints\Data as DataConstraint;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
     *                                                    cascade={"persist", "remove", "detach"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @var ValueInterface[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\ValueInterface", mappedBy="data",
     *                                                    cascade={"persist", "remove", "detach"}, orphanRemoval=true)
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
     * This value is meant to mark objects when var_dumping them so they don't return all their values to prevent too
     * much strain on the database.
     *
     * @var bool
     */
    protected $debugByReference = false;

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
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function addValueData(AttributeInterface $attribute, $valueData, array $context = null)
    {
        $this->checkAttribute($attribute);
        if (!$attribute->isCollection()) {
            $m = "Cannot append data to a non-collection attribute '{$attribute->getCode()}'";
            throw new InvalidValueDataException($m);
        }
        $newValue = $this->createValue($attribute, $context);
        $accessor = PropertyAccess::createPropertyAccessor();
        $position = -1;
        foreach ($this->getValues($attribute, $context) as $value) {
            $position = max($position, $value->getPosition());
        }
        $newValue->setPosition($position + 1);
        try {
            $accessor->setValue($newValue, $attribute->getType()->getDatabaseType(), $valueData);
        } catch (ExceptionInterface $e) {
            throw new InvalidValueDataException("Invalid data for attribute {$attribute->getCode()}", 0, $e);
        }

        return $this;
    }

    /**
     * Remove a data from an attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $valueData
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function removeValueData(AttributeInterface $attribute, $valueData, array $context = null)
    {
        $this->checkAttribute($attribute);
        if (!$attribute->isCollection()) {
            $m = "Cannot remove data from a non-collection attribute '{$attribute->getCode()}'";
            throw new InvalidValueDataException($m);
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->getValues($attribute, $context) as $value) {
            try {
                if ($accessor->getValue($value, $attribute->getType()->getDatabaseType()) === $valueData) {
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
     * @param array $context
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
        if (empty($label) && $this->getIdentifier()) {
            $label = "[{$this->getIdentifier()}]";
        }

        return $label;
    }

    /**
     * Get the value data of the value matching the attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
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

        return count($valuesData) === 0 ? null : $valuesData->first();
    }

    /**
     * Get the values data of multiple values for a given attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return ArrayCollection
     */
    public function getValuesData(AttributeInterface $attribute, array $context = null)
    {
        $this->checkAttribute($attribute);
        $valuesData = new ArrayCollection();
        $accessor = PropertyAccess::createPropertyAccessor();
        $attributeType = $attribute->getType();
        try {
            foreach ($this->getValues($attribute, $context) as $value) {
                $valuesData->add($accessor->getValue($value, $attributeType->getDatabaseType()));
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
        $class = get_class($this);

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
                    throw new \BadMethodCallException($baseErrorMsg.' requires at least one argument');
                }
                $context = array_key_exists(1, $arguments) ? $arguments[1] : null;
                $attributeCode = lcfirst(substr($methodName, strlen($action)));

                return $this->$action($attributeCode, $arguments[0], $context);
            }
        }

        throw new \BadMethodCallException($baseErrorMsg.' does not exist');
    }

    /**
     * Used to get values in a simple way
     *
     * @param string $attributeCode
     * @param array  $context
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
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
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
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
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
     * @param string $attributeCode
     * @param mixed  $value
     * @param array  $context
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
     * @param array              $context
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

        return count($values) === 0 ? null : $values->first();
    }

    /**
     * Return all values matching the attribute code
     *
     * @param AttributeInterface|null $attribute
     * @param array                   $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return Collection|ValueInterface[]
     */
    public function getValues(AttributeInterface $attribute = null, array $context = null)
    {
        if (!$context) {
            $context = $this->getCurrentContext();
        }

        if (null === $attribute) {
            $values = new ArrayCollection();
            foreach ($this->values as $value) {
                $attribute = $this->getFamily()->getAttribute($value->getAttributeCode());
                if ($attribute->isContextMatching($value, $context)) {
                    $values->add($value);
                }
            }

            return $values;
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
        if (0 === count($values) && null !== $attribute->getDefault()) {
            return $this->createDefaultValues($attribute, $context);
        }

        return $values;
    }

    /**
     * @param FamilyInterface|null    $family
     * @param AttributeInterface|null $attribute
     * @param array                   $context
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
     * @param array                   $context
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
        foreach ($this->getRefererValues($family, $attribute) as $refererValue) {
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
        $this->currentContext = $currentContext;
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
     * @param array              $context
     *
     * @throws MissingAttributeException
     *
     * @return ValueInterface
     */
    public function createValue(AttributeInterface $attribute, array $context = null)
    {
        $this->checkAttribute($attribute);
        if (!$context) {
            $context = $this->getCurrentContext();
        }

        return $this->getFamily()->createValue($this, $attribute, $context);
    }

    /**
     * Set the values' data of a given attribute for multiple fields
     *
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function setValuesData(AttributeInterface $attribute, $dataValues, array $context = null)
    {
        $this->emptyValues($attribute, $context);
        $this->setInternalValuesData($attribute, $dataValues, $context);

        return $this;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function emptyValues(AttributeInterface $attribute = null, array $context = null)
    {
        $values = $this->getValues($attribute, $context);
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
        $this->values->removeElement($value);
        $value->setData(null);

        $this->removeValueByAttribute($value);

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
     * @param array              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     * @throws ContextException
     *
     * @return DataInterface
     */
    public function setValueData(AttributeInterface $attribute, $dataValue, array $context = null)
    {
        $value = $this->getValue($attribute, $context);
        if (!$value) {
            $value = $this->createValue($attribute, $context);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            $accessor->setValue($value, $attribute->getType()->getDatabaseType(), $dataValue);
        } catch (ExceptionInterface $e) {
            throw new InvalidValueDataException("Invalid data for attribute {$attribute->getCode()}", 0, $e);
        }

        return $this;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
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
            if ($valueData !== null && $valueData !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove id on clone and clean values
     *
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
     * Automatically append EAV Data as debug info
     *
     * @internal
     *
     * @return array
     */
    public function __debugInfo()
    {
        $reflClass = new \ReflectionClass($this);
        $data = [];
        foreach ($reflClass->getProperties() as $property) {
            try {
                $property->setAccessible(true);
                $data[$property->getName()] = $property->getValue($this);
            } catch (\Exception $e) {
                $data[$property->getName()] = 'ERROR: '.$e->getMessage();
            }
        }

        if ($this->debugByReference) {
            $data['__debugByReference'] = true;

            return $data;
        }

        foreach ($this->getFamily()->getAttributes() as $attribute) {
            try {
                $value = $this->get($attribute->getCode());
                $data[$attribute->getCode()] = $this->handleDebugValue($value);
            } catch (\Exception $e) {
                $data[$attribute->getCode()] = 'ERROR: '.$e->getMessage();
            }
        }

        return $data;
    }

    /**
     * @param bool $value
     */
    protected function setDebugByReference($value)
    {
        $this->debugByReference = $value;
    }

    /**
     * @internal
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function handleDebugValue($value)
    {
        if ($value instanceof AbstractData) {
            $value->setDebugByReference(true);
        }
        if (is_array($value) || $value instanceof \Traversable) {
            /** @var array $value */
            foreach ($value as &$item) {
                if ($item instanceof AbstractData) {
                    $item->setDebugByReference(true);
                }
            }
            unset($item);
        }

        return $value;
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
     * @param AttributeInterface|null $attribute
     * @param array|null              $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     *
     * @return Collection|ValueInterface[]
     */
    protected function createDefaultValues(AttributeInterface $attribute = null, array $context = null)
    {
        $default = $attribute->getDefault();
        if (!$attribute->isCollection()) {
            $default = (array) $default;
        }

        return $this->setInternalValuesData($attribute, $default, $context);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array|null         $context
     *
     * @throws InvalidValueDataException
     * @throws MissingAttributeException
     *
     * @return Collection|ValueInterface[]
     */
    protected function setInternalValuesData(AttributeInterface $attribute, $dataValues, array $context = null)
    {
        $this->checkAttribute($attribute);
        if (!(is_array($dataValues) || $dataValues instanceof \Traversable)) {
            $type = is_object($dataValues) ? get_class($dataValues) : gettype($dataValues);
            throw new InvalidValueDataException(
                "Value for collection attribute {$attribute->getCode()} must be an array, '{$type}' given"
            );
        }
        $values = new ArrayCollection();
        $accessor = PropertyAccess::createPropertyAccessor();
        $position = 0;
        foreach ($dataValues as $dataValue) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $value = $this->createValue($attribute, $context);
            $value->setPosition($position++);
            try {
                $accessor->setValue($value, $attribute->getType()->getDatabaseType(), $dataValue);
            } catch (ExceptionInterface $e) {
                $m = "Invalid data for attribute {$attribute->getCode()} at position {$position}";
                throw new InvalidValueDataException($m, 0, $e);
            }
            $values->add($value);
        }

        return $values;
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
    }

    /**
     * @param array $context
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
        if (null === $this->valuesByAttributes) {
            $this->valuesByAttributes = [];
        }

        if (!array_key_exists($attribute->getCode(), $this->valuesByAttributes)) {
            $this->valuesByAttributes[$attribute->getCode()] = [];
            foreach ($this->values as $value) {
                if ($value->getAttributeCode() === $attribute->getCode()) {
                    $this->addValueByAttribute($value);
                }
            }
        }

        return $this->valuesByAttributes[$attribute->getCode()];
    }

    /**
     * @param ValueInterface $value
     */
    protected function addValueByAttribute(ValueInterface $value)
    {
        if (null === $this->valuesByAttributes) {
            $this->valuesByAttributes = [];
        }

        if (!array_key_exists($value->getAttributeCode(), $this->valuesByAttributes)) {
            return; // No cache so no need to update anything
        }

        $key = spl_object_hash($value);
        $this->valuesByAttributes[$value->getAttributeCode()][$key] = $value;
    }

    /**
     * @param ValueInterface $value
     */
    protected function removeValueByAttribute(ValueInterface $value)
    {
        if (null === $this->valuesByAttributes) {
            $this->valuesByAttributes = [];
        }

        if (!array_key_exists($value->getAttributeCode(), $this->valuesByAttributes)) {
            return; // No cache so no need to do anything
        }

        $key = spl_object_hash($value);
        unset($this->valuesByAttributes[$value->getAttributeCode()][$key]);
    }
}
