<?php

namespace Sidus\EAVModelBundle\Entity;

use BadMethodCallException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JMS\Serializer\Annotation as JMS;
use LogicException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
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
abstract class Data implements ContextualDataInterface
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
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="cascade")
     */
    protected $parent;

    /**
     * @var DataInterface[]
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\DataInterface", mappedBy="parent", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @var ValueInterface[]|Collection
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\ValueInterface", mappedBy="data", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     * @JMS\Exclude()
     */
    protected $values;

    /**
     * @var DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var FamilyInterface
     * @ORM\Column(name="family_code", type="sidus_family", length=255)
     * @JMS\Exclude()
     */
    protected $family;

    /**
     * @var int
     * @ORM\Column(name="current_version", type="integer")
     */
    protected $currentVersion = 0;

    /**
     * @var array
     */
    protected $currentContext;

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
     * @return mixed
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function getIdentifier()
    {
        $identifierAttribute = $this->getFamily()->getAttributeAsIdentifier();
        if ($identifierAttribute) {
            return $this->getValuesData($identifierAttribute);
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
     * @return DataInterface
     *
     * @throws UnexpectedValueException
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
     * @return DataInterface
     *
     * @throws UnexpectedValueException
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
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function addValueData(AttributeInterface $attribute, $valueData, array $context = null)
    {
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
     * @return mixed
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @return mixed
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function getValuesData(AttributeInterface $attribute = null, array $context = null)
    {
        $valuesData = new ArrayCollection();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->getValues($attribute, $context) as $value) {
            $valuesData->add($accessor->getValue($value, $attribute->getType()->getDatabaseType()));
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
     * @return mixed|null|ValueInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws BadMethodCallException
     */
    public function __call($methodName, $arguments)
    {
        $class = get_class($this);

        if (0 === strpos($methodName, 'get')) {
            return $this->get(lcfirst(substr($methodName, 3)));
        }

        if (0 === strpos($methodName, 'set')) {
            if (!array_key_exists(0, $arguments)) {
                throw new BadMethodCallException("Method '{$methodName}' for object '{$class}' with family '{$this->getFamilyCode()}' requires at least one argument");
            }
            $context = array_key_exists(1, $arguments) ? $arguments[1] : null;

            return $this->set(lcfirst(substr($methodName, 3)), $arguments[0], $context);
        }

        throw new BadMethodCallException("Method '{$methodName}' for object '{$class}' with family '{$this->getFamilyCode()}' does not exist");
    }

    /**
     * Used to get values in a simple way
     *
     * @param string $attributeCode
     * @param array  $context
     * @return mixed
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws BadMethodCallException
     */
    public function get($attributeCode, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $getter = 'get'.ucfirst($attributeCode);
        if (method_exists($this, $getter)) {
            return $this->$getter($attributeCode, $context);
        }

        if ($attribute->isMultiple()) {
            return $this->getValuesData($attribute, $context);
        }

        return $this->getValueData($attribute, $context);
    }

    /**
     * @param string $attributeCode
     * @return mixed
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws BadMethodCallException
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
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function set($attributeCode, $value, array $context = null)
    {
        $attribute = $this->getAttribute($attributeCode);

        $setter = 'set'.ucfirst($attributeCode);
        if (method_exists($this, $setter)) {
            return $this->$setter($attributeCode, $value, $context);
        }

        if ($attribute->isMultiple()) {
            return $this->setValuesData($attribute, $value, $context);
        }

        return $this->setValueData($attribute, $value, $context);
    }

    /**
     * @param string $attributeCode
     * @param mixed  $value
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function __set($attributeCode, $value)
    {
        return $this->set($attributeCode, $value);
    }

    /**
     * @param string $attributeCode
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
     * @return null|ValueInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @return Collection|ValueInterface[]
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
        foreach ($this->values as $value) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if ($value->getAttributeCode() === $attribute->getCode()
                && $attribute->isContextMatching($value, $context)
            ) {
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
     * @return FamilyInterface
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("family")
     * @return string
     */
    public function getFamilyCode()
    {
        return $this->getFamily()->getCode();
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return ValueInterface
     */
    public function createValue(AttributeInterface $attribute, array $context = null)
    {
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
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @return DataInterface
     */
    public function removeValue(ValueInterface $value)
    {
        $this->values->removeElement($value);
        $value->setData(null);

        return $this;
    }

    /**
     * @param ValueInterface $value
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     */
    public function addValue(ValueInterface $value)
    {
        if ($value instanceof ContextualValueInterface && !$value->getContext()) {
                $value->setContext($this->getCurrentContext());
        }
        $this->values->add($value);
        $value->setData($this);

        return $this;
    }

    /**
     * Set the value's data of a given attribute
     *
     * @param AttributeInterface $attribute
     * @param mixed              $dataValue
     * @param array              $context
     * @return DataInterface
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function setValueData(AttributeInterface $attribute, $dataValue, array $context = null)
    {
        return $this->setValuesData($attribute, [$dataValue], $context);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return bool
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
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
     * @return int
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * @param int $currentVersion
     */
    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
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
        $newValues = new ArrayCollection();
        foreach ($this->getValues() as $value) {
            $newValues[] = clone $value;
        }
        $this->emptyValues();
        foreach ($newValues as $newValue) {
            $this->addValue($newValue);
        }
        $this->setCreatedAt(new DateTime());
    }

    /**
     * @param $attributeCode
     * @return AttributeInterface
     */
    protected function getAttribute($attributeCode)
    {
        return $this->getFamily()->getAttribute($attributeCode);
    }

    /**
     * @param AttributeInterface|null $attribute
     * @param array|null              $context
     * @return Collection|ValueInterface[]
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    protected function createDefaultValues(AttributeInterface $attribute = null, array $context = null)
    {
        $default = $attribute->getDefault();
        if (!$attribute->isMultiple()) {
            $default = [$default];
        }

        return $this->setInternalValuesData($attribute, $default, $context);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array|\Traversable $dataValues
     * @param array|null         $context
     * @return Collection|ValueInterface[]
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    protected function setInternalValuesData(AttributeInterface $attribute, $dataValues, array $context = null)
    {
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
     * @return string
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    protected function getLabelValue()
    {
        return (string) $this->getValueData($this->getFamily()->getAttributeAsLabel());
    }
}
