<?php

namespace Sidus\EAVModelBundle\Entity;

use BadMethodCallException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use LogicException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Model\IdentifierAttributeType;
use Sidus\EAVModelBundle\Utilities\DateTimeUtility;
use Sidus\EAVModelBundle\Validator\Constraints\Data as DataConstraint;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use UnexpectedValueException;

/**
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
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", mappedBy="parent", cascade={"persist",
     *                                                                          "remove"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @var ValueInterface[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\ValueInterface", mappedBy="data", cascade={"persist",
     *                                                                           "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $values;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
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
     * @var string
     *
     * @ORM\Column(name="string_identifier", type="string", length=255, nullable=true)
     */
    protected $stringIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="integer_identifier", type="integer", nullable=true)
     */
    protected $integerIdentifier;

    /**
     * Used as an internal cache to access more easily values based on their attribute
     *
     * @var ValueInterface[][]
     */
    protected $valuesByAttributes;

    /**
     * Initialize the data with an optional (but recommended family code)
     *
     * @param FamilyInterface $family
     *
     * @throws LogicException
     */
    public function __construct(FamilyInterface $family)
    {
        if (!$family->isInstantiable()) {
            throw new LogicException("Family {$family->getCode()} is not instantiable");
        }
        $this->family = $family;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->values = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        $identifierAttribute = $this->getFamily()->getAttributeAsIdentifier();
        if ($identifierAttribute) {
            return $this->get($identifierAttribute->getCode());
        }

        return $this->getId();
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @throws UnexpectedValueException
     *
     * @return DataInterface
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = DateTimeUtility::parse($createdAt, false);

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     *
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     *
     * @return DataInterface
     */
    public function addValueData(AttributeInterface $attribute, $valueData, array $context = null)
    {
        $this->checkAttribute($attribute);
        if (!$attribute->isCollection()) {
            $m = "Cannot append data to non-multiple attribute '{$attribute->getCode()}'";
            throw new UnexpectedValueException($m);
        }
        $newValue = $this->createValue($attribute, $context);
        $accessor = PropertyAccess::createPropertyAccessor();
        $position = -1;
        foreach ($this->getValues($attribute, $context) as $value) {
            $position = max($position, $value->getPosition());
        }
        $newValue->setPosition($position + 1);
        $accessor->setValue($newValue, $attribute->getType()->getDatabaseType(), $valueData);

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        try {
            return $this->getLabelValue();
        } catch (Exception $e) {
            return "[{$this->getId()}]";
        }
    }

    /**
     * Get the value data of the value matching the attribute
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     *
     * @return ArrayCollection
     */
    public function getValuesData(AttributeInterface $attribute = null, array $context = null)
    {
        $this->checkAttribute($attribute);
        $valuesData = new ArrayCollection();
        $accessor = PropertyAccess::createPropertyAccessor();
        $attributeType = $attribute->getType();
        if ($attributeType instanceof IdentifierAttributeType) {
            $valuesData->add($accessor->getValue($this, $attributeType->getDatabaseType()));
        } else {
            foreach ($this->getValues($attribute, $context) as $value) {
                $valuesData->add($accessor->getValue($value, $attributeType->getDatabaseType()));
            }
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
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Magic method to allow direct access to EAV model
     *
     * @param string $methodName
     * @param array  $arguments
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws BadMethodCallException
     *
     * @return mixed|null|ValueInterface
     */
    public function __call($methodName, $arguments)
    {
        $class = get_class($this);

        if (0 === strpos($methodName, 'get')) {
            $context = array_key_exists(0, $arguments) ? $arguments[0] : null;

            return $this->get(lcfirst(substr($methodName, 3)), $context);
        }
        $baseErrorMsg = "Method '{$methodName}' for object '{$class}' with family '{$this->getFamilyCode()}'";

        if (0 === strpos($methodName, 'set')) {
            if (!array_key_exists(0, $arguments)) {
                throw new BadMethodCallException($baseErrorMsg.' requires at least one argument');
            }
            $context = array_key_exists(1, $arguments) ? $arguments[1] : null;

            return $this->set(lcfirst(substr($methodName, 3)), $arguments[0], $context);
        }

        throw new BadMethodCallException($baseErrorMsg.' does not exist');
    }

    /**
     * Used to get values in a simple way
     *
     * @param string $attributeCode
     * @param array  $context
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws BadMethodCallException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws BadMethodCallException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     *
     * @return DataInterface
     */
    public function set($attributeCode, $value, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $setter = 'set'.ucfirst($attributeCode);
        if (method_exists($this, $setter)) {
            return $this->$setter($value, $context);
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * Return first value found for attribute code in value collection
     *
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @return string
     */
    public function getStringIdentifier()
    {
        return $this->stringIdentifier;
    }

    /**
     * @param string $stringIdentifier
     *
     * @return AbstractData
     */
    public function setStringIdentifier($stringIdentifier)
    {
        $this->stringIdentifier = $stringIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getIntegerIdentifier()
    {
        return $this->integerIdentifier;
    }

    /**
     * @param string $integerIdentifier
     *
     * @return AbstractData
     */
    public function setIntegerIdentifier($integerIdentifier)
    {
        $this->integerIdentifier = $integerIdentifier;

        return $this;
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
     * @throws \UnexpectedValueException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     *
     * @return DataInterface
     */
    public function setValueData(AttributeInterface $attribute, $dataValue, array $context = null)
    {
        if ($attribute->getType() instanceof IdentifierAttributeType) {
            $value = $this;
        } else {
            $value = $this->getValue($attribute, $context);
            if (!$value) {
                $value = $this->createValue($attribute, $context);
            }
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($value, $attribute->getType()->getDatabaseType(), $dataValue);

        return $this;
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function __clone()
    {
        $this->id = null;
        $this->stringIdentifier = null;
        $this->integerIdentifier = null;
        $this->valuesByAttributes = null;

        $newValues = new ArrayCollection();
        foreach ($this->values as $value) {
            $newValues[] = clone $value;
        }
        $this->values = new ArrayCollection();
        foreach ($newValues as $newValue) {
            $this->values->add($newValue);
            $newValue->setData($this);
        }
        $this->setCreatedAt(new DateTime());
    }

    /**
     * @param string $attributeCode
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     *
     * @return Collection|ValueInterface[]
     */
    protected function setInternalValuesData(AttributeInterface $attribute, $dataValues, array $context = null)
    {
        $this->checkAttribute($attribute);
        if (!(is_array($dataValues) || $dataValues instanceof \Traversable)) {
            $type = is_object($dataValues) ? get_class($dataValues) : gettype($dataValues);
            throw new UnexpectedValueException("Value for multiple attribute {$attribute->getCode()} must be an array, '{$type}' given");
        }
        $values = new ArrayCollection();
        $accessor = PropertyAccess::createPropertyAccessor();
        $position = 0;
        foreach ($dataValues as $dataValue) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $value = $this->createValue($attribute, $context);
            $value->setPosition($position++);
            $accessor->setValue($value, $attribute->getType()->getDatabaseType(), $dataValue);
            $values->add($value);
        }

        return $values;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @throws UnexpectedValueException
     */
    protected function checkAttribute(AttributeInterface $attribute)
    {
        if (!$this->getFamily()->hasAttribute($attribute->getCode())) {
            throw new UnexpectedValueException("Attribute {$attribute->getCode()} doesn't exists in family {$this->getFamilyCode()}");
        }
    }

    /**
     * @throws \BadMethodCallException
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     *
     * @return string
     */
    protected function getLabelValue()
    {
        $attributeAsLabel = $this->getFamily()->getAttributeAsLabel();
        if ($attributeAsLabel) {
            return (string) $this->getValueData($attributeAsLabel);
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

        $this->valuesByAttributes[$value->getAttributeCode()][$value->getIdentifier()] = $value;
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

        unset($this->valuesByAttributes[$value->getAttributeCode()][$value->getIdentifier()]);
    }
}
