<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Configuration\AttributeConfigurationHandler;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Context\ContextManager;
use Sidus\EAVModelBundle\Entity\ContextualValueInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use UnexpectedValueException;

/**
 * Defines the model of a data, think of it as the data type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Family implements FamilyInterface
{
    use TranslatableTrait;

    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var string */
    protected $code;

    /** @var string */
    protected $type;

    /** @var string */
    protected $label;

    /** @var Attribute */
    protected $attributeAsLabel;

    /** @var Attribute */
    protected $attributeAsIdentifier;

    /** @var Attribute[] */
    protected $attributes = [];

    /** @var Family */
    protected $parent;

    /** @var bool */
    protected $instantiable;

    /** @var bool */
    protected $singleton;

    /** @var Family[] */
    protected $children;

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $valueClass;

    /** @var ContextManager */
    protected $contextManager;

    /**
     * @param string                        $code
     * @param AttributeConfigurationHandler $attributeConfigurationHandler
     * @param FamilyConfigurationHandler    $familyConfigurationHandler
     * @param ContextManager                $contextManager
     * @param array                         $config
     *
     * @throws UnexpectedValueException
     * @throws MissingFamilyException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function __construct(
        $code,
        AttributeConfigurationHandler $attributeConfigurationHandler,
        FamilyConfigurationHandler $familyConfigurationHandler,
        ContextManager $contextManager,
        array $config = null
    ) {
        $this->code = $code;
        $this->familyConfigurationHandler = $familyConfigurationHandler;
        $this->contextManager = $contextManager;

        if (!empty($config['parent'])) {
            $this->parent = $familyConfigurationHandler->getFamily($config['parent']);
            $this->copyFromFamily($this->parent);
        }
        unset($config['parent']);

        foreach ((array) $config['attributes'] as $attribute) {
            $this->attributes[$attribute] = $attributeConfigurationHandler->getAttribute($attribute);
        }
        unset($config['attributes']);

        if (!empty($config['attributeAsLabel'])) {
            $labelCode = $config['attributeAsLabel'];
            if (!$this->hasAttribute($labelCode)) {
                $message = "Bad configuration for family {$code}: attribute as label '{$labelCode}'";
                $message .= " doesn't exists for this family";
                throw new UnexpectedValueException($message);
            }
            $this->attributeAsLabel = $this->getAttribute($labelCode);
        }
        unset($config['attributeAsLabel']);

        if (!empty($config['attributeAsIdentifier'])) {
            $labelCode = $config['attributeAsIdentifier'];
            $commonMessage = "Bad configuration for family {$code}: attribute as identifier '{$labelCode}'";
            if (!$this->hasAttribute($labelCode)) {
                throw new UnexpectedValueException("{$commonMessage} doesn't exists for this family");
            }
            $this->attributeAsIdentifier = $this->getAttribute($labelCode);
            if (!$this->attributeAsIdentifier->isUnique()) {
                throw new UnexpectedValueException("{$commonMessage} should be unique");
            }
            if (!$this->attributeAsIdentifier->isRequired()) {
                throw new UnexpectedValueException("{$commonMessage} should be required");
            }
            if ($this->attributeAsIdentifier->isCollection()) {
                throw new UnexpectedValueException("{$commonMessage} should NOT be a collection");
            }
            if (0 !== count($this->attributeAsIdentifier->getContextMask())) {
                throw new UnexpectedValueException("{$commonMessage} should NOT be contextualized");
            }
        }
        unset($config['attributeAsIdentifier']);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($config as $key => $value) {
            $accessor->setValue($this, $key, $value);
        }
    }

    /**
     * @return Attribute
     */
    public function getAttributeAsLabel()
    {
        return $this->attributeAsLabel;
    }

    /**
     * @param Attribute $attributeAsLabel
     */
    public function setAttributeAsLabel(Attribute $attributeAsLabel)
    {
        $this->attributeAsLabel = $attributeAsLabel;
    }

    /**
     * @return Attribute|null
     */
    public function getAttributeAsIdentifier()
    {
        return $this->attributeAsIdentifier;
    }

    /**
     * @param Attribute $attributeAsIdentifier
     */
    public function setAttributeAsIdentifier(Attribute $attributeAsIdentifier)
    {
        $this->attributeAsIdentifier = $attributeAsIdentifier;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function addAttribute(AttributeInterface $attribute)
    {
        $this->attributes[$attribute->getCode()] = $attribute;
    }

    /**
     * @return Family
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Family $parent
     */
    public function setParent(Family $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return Family[]
     */
    public function getChildren()
    {
        if (null === $this->children) {
            $this->children = $this->familyConfigurationHandler->getByParent($this);
        }

        return $this->children;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return AttributeInterface
     * @throws UnexpectedValueException
     */
    public function getAttribute($code)
    {
        if (!$this->hasAttribute($code)) {
            throw new UnexpectedValueException("Unknown attribute {$code} in family {$this->code}");
        }

        return $this->attributes[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasAttribute($code)
    {
        return !empty($this->attributes[$code]);
    }

    /**
     * @return bool
     */
    public function isInstantiable()
    {
        return $this->instantiable;
    }

    /**
     * @param boolean $instantiable
     */
    public function setInstantiable($instantiable)
    {
        $this->instantiable = $instantiable;
    }

    /**
     * @return boolean
     */
    public function isSingleton()
    {
        return $this->singleton;
    }

    /**
     * @param boolean $singleton
     */
    public function setSingleton($singleton)
    {
        $this->singleton = $singleton;
    }

    /**
     * Will check the translator for the key "eav.family.{$code}.label"
     * and humanize the code if no translation is found
     *
     * @return string
     */
    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }

        return $this->tryTranslate("eav.family.{$this->getCode()}.label", [], $this->getCode());
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getLabel();
    }

    /**
     * Return current family code and all it's sub-families codes
     *
     * @return array
     */
    public function getMatchingCodes()
    {
        $codes = [$this->getCode()];
        foreach ($this->getChildren() as $child) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $codes = array_merge($codes, $child->getMatchingCodes());
        }

        return $codes;
    }

    /**
     * @return string
     */
    public function getValueClass()
    {
        return $this->valueClass;
    }

    /**
     * @param string $valueClass
     */
    public function setValueClass($valueClass)
    {
        $this->valueClass = $valueClass;
    }

    /**
     * @return string
     */
    public function getDataClass()
    {
        return $this->dataClass;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param DataInterface      $data
     * @param AttributeInterface $attribute
     * @param array              $context
     *
     * @return ValueInterface
     * @throws UnexpectedValueException
     */
    public function createValue(DataInterface $data, AttributeInterface $attribute, array $context = null)
    {
        $valueClass = $this->getValueClass();
        /** @var ValueInterface $value */
        $value = new $valueClass($data, $attribute);
        $data->addValue($value);

        if ($value instanceof ContextualValueInterface && count($attribute->getContextMask())) {
            /** @var ContextualValueInterface $value */
            if (!$context) {
                $context = $this->getContext();
            }
            foreach ($attribute->getContextMask() as $key) {
                $value->setContextValue($key, $context[$key]);
            }
        }

        return $value;
    }

    /**
     * @return DataInterface
     * @throws \LogicException
     */
    public function createData()
    {
        if (!$this->isInstantiable()) {
            throw new \LogicException("Family {$this->getCode()} is not instantiable");
        }
        if ($this->isSingleton()) {
            throw new \LogicException(
                "Family {$this->getCode()} is a singleton, use the repository to retrieve the instance"
            );
        }
        $dataClass = $this->getDataClass();

        return new $dataClass($this);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->contextManager->getContext();
    }

    /**
     * @param FamilyInterface $parent
     */
    protected function copyFromFamily(FamilyInterface $parent)
    {
        foreach ($parent->getAttributes() as $attribute) {
            $this->addAttribute($attribute);
        }
        $this->attributeAsLabel = $parent->getAttributeAsLabel();
        $this->attributeAsIdentifier = $parent->getAttributeAsIdentifier();
        $this->valueClass = $parent->getValueClass();
        $this->dataClass = $parent->getDataClass();
    }
}
