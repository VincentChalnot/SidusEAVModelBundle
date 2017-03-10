<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Configuration\AttributeTypeConfigurationHandler;
use Sidus\EAVModelBundle\Entity\ContextualValueInterface;
use Sidus\EAVModelBundle\Exception\AttributeConfigurationException;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use UnexpectedValueException;

/**
 * Define an attribute in the EAV model
 *
 * @todo : The properties "family" and "families" should be "allowedFamily" and "allowedFamilies" and the "family"
 * @todo property should be set when adding an attribute to a family because attributes can't exists outside of families
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

    /** @var FamilyInterface */
    protected $family;

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
     * @throws AttributeConfigurationException
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
     * @return FamilyInterface
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @param FamilyInterface $family
     */
    public function setFamily(FamilyInterface $family)
    {
        $this->family = $family;
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
        if (!$this->isMultiple()) {
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
     * @param boolean $multiple
     */
    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;
    }

    /**
     * When an attribute is multiple, it's also a collection
     *
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
        $tIds = [];
        if ($this->getFamily()) {
            $tIds[] = "eav.family.{$this->getFamily()->getCode()}.attribute.{$this->getCode()}.label";
        }
        $tIds[] = "eav.attribute.{$this->getCode()}.label";

        return $this->tryTranslate($tIds, [], $this->getCode());
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
     * @throws ContextException
     *
     * @return bool
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
     * @param array $configuration
     *
     * @throws AttributeConfigurationException
     */
    public function mergeConfiguration(array $configuration)
    {
        if (isset($configuration['type'])) {
            try {
                $newType = $this->attributeTypeConfigurationHandler->getType($configuration['type']);
            } catch (\UnexpectedValueException $e) {
                throw new AttributeConfigurationException(
                    "The attribute {$this->code} has an unknown type '{$configuration['type']}'",
                    0,
                    $e
                );
            }
            if ($this->type && $this->type->getDatabaseType() !== $newType->getDatabaseType()) {
                $e = "The attribute '{$this->code}' cannot be overridden with a new attribute type that don't match ";
                $e .= "the database type '{$this->type->getDatabaseType()}'";
                throw new AttributeConfigurationException($e);
            }
            $this->type = $newType;
            unset($configuration['type']);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($configuration as $key => $value) {
            try {
                $accessor->setValue($this, $key, $value);
            } catch (\Exception $e) {
                throw new AttributeConfigurationException(
                    "The attribute {$this->code} has an invalid configuration for option '{$key}'",
                    0,
                    $e
                );
            }
        }

        $this->type->setAttributeDefaults($this); // Allow attribute type service to configure attribute

        $this->checkConflicts();
    }

    /**
     * @throws AttributeConfigurationException
     */
    protected function checkConflicts()
    {
        $default = $this->getDefault();
        if ($this->isCollection()) {
            if ($this->isUnique()) {
                throw new AttributeConfigurationException(
                    "Attribute {$this->getCode()} cannot be a collection and unique at the same time"
                );
            }
            if (null !== $default && !(is_array($default) || $default instanceof \Traversable)) {
                $e = "Attribute {$this->getCode()} is a collection and therefore should have an array of values for";
                $e .= ' the default option';
                throw new AttributeConfigurationException($e);
            }
        } elseif ($this->isMultiple()) {
            throw new AttributeConfigurationException(
                "Attribute {$this->getCode()} cannot be multiple and not a collection"
            );
        }

        if ($this->getType()->isRelation() || $this->getType()->isEmbedded()) {
            if ($default !== null) {
                $e = "Attribute {$this->getCode()} is a relation to an other entity, it doesn't support default values";
                $e .= ' in configuration';
                throw new AttributeConfigurationException($e);
            }
        }
    }
}
