<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Configuration\AttributeTypeConfigurationHandler;
use Sidus\EAVModelBundle\Entity\ContextualValueInterface;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use UnexpectedValueException;

/**
 * Define an attribute in the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Attribute implements AttributeInterface
{
    use TranslatableTrait;

    /** @var AttributeTypeConfigurationHandler */
    protected $attributeTypeConfigurationHandler;

    /** @var string */
    protected $code;

    /** @var string */
    protected $label;

    /** @var AttributeType */
    protected $type;

    /** @var string */
    protected $family;

    /** @var array */
    protected $families;

    /** @var string */
    protected $group;

    /** @var array */
    protected $options = [];

    /** @var string */
    protected $formType;

    /** @var array */
    protected $formOptions = [];

    /** @var array */
    protected $viewOptions = [];

    /** @var array */
    protected $validationRules = [];

    /** @var bool */
    protected $required = false;

    /** @var bool */
    protected $unique = false;

    /** @var bool */
    protected $multiple = false;

    /** @var bool */
    protected $collection; // Important to left null

    /** @var array */
    protected $contextMask = [];

    /** @var mixed */
    protected $default;

    /**
     * @param string                            $code
     * @param AttributeTypeConfigurationHandler $attributeTypeConfigurationHandler
     * @param array                             $configuration
     *
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     * @throws \LogicException
     */
    public function __construct(
        $code,
        AttributeTypeConfigurationHandler $attributeTypeConfigurationHandler,
        array $configuration = null
    ) {
        $this->code = $code;
        $this->attributeTypeConfigurationHandler = $attributeTypeConfigurationHandler;
        if (!isset($configuration['type'])) {
            $configuration['type'] = 'string';
        }

        $this->mergeConfiguration($configuration);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return AttributeType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @param string $family
     */
    public function setFamily($family)
    {
        $this->family = $family;
    }

    /**
     * @return array
     */
    public function getFamilies()
    {
        return $this->families;
    }

    /**
     * @param array $families
     */
    public function setFamilies($families)
    {
        $this->families = $families;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function getOption($code)
    {
        if (!array_key_exists($code, $this->options)) {
            return null;
        }

        return $this->options[$code];
    }

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addOption($code, $value)
    {
        $this->options[$code] = $value;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        if (null === $this->formType) {
            return $this->getType()->getFormType();
        }

        return $this->formType;
    }

    /**
     * @param string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    public function getFormOptions($data = null)
    {
        $defaultOptions = [];
        if (!$this->isCollection()) {
            $defaultOptions = ['required' => $this->isRequired()];
        }
        $typeOptions = $this->getType()->getFormOptions($this, $data);

        return array_merge($defaultOptions, $typeOptions, $this->formOptions);
    }

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addFormOption($code, $value)
    {
        $this->formOptions[$code] = $value;
    }


    /**
     * @param array $formOptions
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;
    }

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addViewOption($code, $value)
    {
        $this->viewOptions[$code] = $value;
    }

    /**
     * @return array
     */
    public function getViewOptions()
    {
        return $this->viewOptions;
    }

    /**
     * @param array $viewOptions
     */
    public function setViewOptions(array $viewOptions)
    {
        $this->viewOptions = $viewOptions;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param boolean $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return array
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * @param array $options
     */
    public function addValidationRule(array $options)
    {
        $this->validationRules[] = $options;
    }

    /**
     * @param array $validationRules
     */
    public function setValidationRules(array $validationRules)
    {
        $this->validationRules = $validationRules;
    }

    /**
     * @return boolean
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * Most of the time, when an attribute is multiple, it's also a collection
     *
     * @param boolean $multiple
     */
    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        if ($this->collection === null) {
            return $this->isMultiple();
        }

        return $this->collection;
    }

    /**
     * Sometimes an attribute can be multiple but not a collection
     *
     * @param boolean $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }

        return $this->tryTranslate("eav.attribute.{$this->getCode()}.label", [], $this->getCode());
    }

    /**
     * @param string $label
     *
     * @return Attribute
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getLabel();
    }

    /**
     * @return array
     */
    public function getContextMask()
    {
        return $this->contextMask;
    }

    /**
     * @param array $contextMask
     */
    public function setContextMask($contextMask)
    {
        $this->contextMask = $contextMask;
    }

    /**
     * @param ContextualValueInterface $value
     * @param array                    $context
     *
     * @return bool
     * @throws UnexpectedValueException
     */
    public function isContextMatching(ContextualValueInterface $value, array $context)
    {
        if (!$value->getContext()) {
            return true;
        }
        foreach ($this->getContextMask() as $key) {
            $contextKey = array_key_exists($key, $context) ? $context[$key] : null;
            if ($contextKey !== $value->getContextValue($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     *
     * @return Attribute
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @throws UnexpectedValueException
     */
    protected function checkConflicts()
    {
        $default = $this->getDefault();
        if ($this->isCollection()) {
            if ($this->isUnique()) {
                throw new UnexpectedValueException(
                    "Attribute {$this->getCode()} cannot be multiple and unique at the same time"
                );
            }
            if (null !== $default && !(is_array($default) || $default instanceof \Traversable)) {
                throw new UnexpectedValueException(
                    "Attribute {$this->getCode()} is multiple and therefore should have an array of values as default option"
                );
            }
        }
        if ($default !== null && ($this->getType()->isRelation() || $this->getType()->isEmbedded())) {
            throw new UnexpectedValueException(
                "Attribute {$this->getCode()} is a relation to an other entity, it doesn't support default values in configuration"
            );
        }
    }

    /**
     * @param array $configuration
     *
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     * @throws \LogicException
     */
    public function mergeConfiguration(array $configuration)
    {
        if (isset($configuration['type'])) {
            $newType = $this->attributeTypeConfigurationHandler->getType($configuration['type']);
            if ($this->type && $this->type->getDatabaseType() !== $newType->getDatabaseType()) {
                $e = "The attribute '{$this->code}' cannot be overridden with a new attribute type that don't match ";
                $e .= "the database type '{$this->type->getDatabaseType()}'";
                throw new \LogicException($e);
            }
            $this->type = $newType;
            unset($configuration['type']);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($configuration as $key => $value) {
            $accessor->setValue($this, $key, $value);
        }

        $this->type->setAttributeDefaults($this); // Allow attribute type service to configure attribute

        $this->checkConflicts();
    }
}
