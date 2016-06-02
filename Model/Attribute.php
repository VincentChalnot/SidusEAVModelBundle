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

    /** @var string */
    protected $code;

    /** @var string */
    protected $label;

    /** @var AttributeType */
    protected $type;

    /** @var string */
    protected $group;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $formOptions = [];

    /** @var array */
    protected $viewOptions = [];

    /** @var array */
    protected $validationRules = [];

    /** @var bool */
    protected $isRequired = false;
    
    /** @var bool */
    protected $isSortable = false;

    /** @var bool */
    protected $isUnique = false;

    /** @var bool */
    protected $isMultiple = false;

    /** @var bool */
    protected $isCollection; // Important to left null

    /** @var array */
    protected $contextMask = [];

    /** @var mixed */
    protected $default;

    /**
     * @param string                            $code
     * @param AttributeTypeConfigurationHandler $attributeTypeConfigurationHandler
     * @param array                             $configuration
     * @throws UnexpectedValueException
     * @throws AccessException
     * @throws InvalidArgumentException
     * @throws UnexpectedTypeException
     */
    public function __construct(
        $code,
        AttributeTypeConfigurationHandler $attributeTypeConfigurationHandler,
        array $configuration = null
    ) {
        $this->code = $code;
        $this->type = $attributeTypeConfigurationHandler->getType($configuration['type']);
        unset($configuration['type']);

        $this->type->setAttributeDefaults($this); // Allow attribute type service to configure attribute

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($configuration as $key => $value) {
            $accessor->setValue($this, $key, $value);
        }

        $this->checkConflicts();
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function getOption($code)
    {
        if (!isset($this->options[$code])) {
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
     * @param mixed $data
     * @return array
     */
    public function getFormOptions($data = null)
    {
        $defaultOptions = ['required' => $this->isRequired];
        $typeOptions = $this->getType()->getFormOptions($data);

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
        return $this->isRequired;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->isRequired = $required;
    }
    
    /**
     * @return boolean
     */
    public function isSortable()
    {
        return $this->isSortable;
    }

    /**
     * @param boolean $sortable
     */
    public function setSortable($sortable)
    {
        $this->isSortable = $sortable;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->isUnique;
    }

    /**
     * @param boolean $unique
     */
    public function setUnique($unique)
    {
        $this->isUnique = $unique;
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
    public function addValidationRules(array $options)
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
        return $this->isMultiple;
    }

    /**
     * Most of the time, when an attribute is multiple, it's also a collection
     *
     * @param boolean $multiple
     */
    public function setMultiple($multiple)
    {
        $this->isMultiple = $multiple;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        if ($this->isCollection === null) {
            return $this->isMultiple();
        }

        return $this->isCollection;
    }

    /**
     * Sometimes an attribute can be multiple but not a collection
     *
     * @param boolean $collection
     */
    public function setCollection($collection)
    {
        $this->isCollection = $collection;
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
        if ($this->isMultiple()) {
            if ($this->isUnique()) {
                throw new UnexpectedValueException("Attribute {$this->getCode()} cannot be multiple and unique at the same time");
            }
            if (null !== $default && !(is_array($default) || $default instanceof \Traversable)) {
                throw new UnexpectedValueException("Attribute {$this->getCode()} is multiple and therefore should have an array of values as default option");
            }
        }
        if ($default !== null && ($this->getType()->isRelation() || $this->getType()->isEmbedded())) {
            throw new UnexpectedValueException("Attribute {$this->getCode()} is a relation to an other entity, it doesn't support default values in configuration");
        }
    }
}
