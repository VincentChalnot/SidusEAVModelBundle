<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Model;

use Sidus\BaseBundle\Utilities\DebugInfoUtility;
use Sidus\BaseBundle\Utilities\SleepUtility;
use Sidus\EAVModelBundle\Registry\AttributeTypeRegistry;
use Sidus\EAVModelBundle\Entity\ContextualValueInterface;
use Sidus\EAVModelBundle\Exception\AttributeConfigurationException;
use Sidus\EAVModelBundle\Exception\ContextException;
use Sidus\BaseBundle\Translator\TranslatableTrait;
use Symfony\Component\VarDumper\Caster\Caster;

/**
 * Defines an attribute in the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Attribute implements AttributeInterface
{
    use TranslatableTrait;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

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
    protected $contextMask;

    /** @var array */
    protected $globalContextMask = [];

    /** @var mixed */
    protected $default;

    /** @var string */
    protected $fallbackLabel;

    /**
     * @param string                $code
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param array                 $configuration
     * @param array                 $globalContextMask
     *
     * @throws AttributeConfigurationException
     */
    public function __construct(
        $code,
        AttributeTypeRegistry $attributeTypeRegistry,
        array $configuration = null,
        array $globalContextMask = []
    ) {
        $this->code = $code;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->globalContextMask = $globalContextMask;
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
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($code, $default = null)
    {
        if (!array_key_exists($code, $this->options)) {
            return $default;
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
     * @return array
     */
    public function getFormOptions()
    {
        $defaultOptions = [];
        if (!$this->isMultiple()) {
            $defaultOptions = ['required' => $this->isRequired()];
        }
        $typeOptions = $this->getType()->getFormOptions($this);

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
        if (null === $this->collection) {
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
        if (null === $this->translator) {
            return $this->fallbackLabel; // Use fallback after serialization
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
        if (null === $this->contextMask) {
            return $this->globalContextMask;
        }

        return $this->contextMask;
    }

    /**
     * @param array $contextMask
     */
    public function setContextMask(array $contextMask)
    {
        $this->contextMask = $contextMask;
    }

    /**
     * Warning, the context array must contain all context axis
     *
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
        $value::checkContext($context);
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
                $newType = $this->attributeTypeRegistry->getType($configuration['type']);
            } catch (\UnexpectedValueException $e) {
                $attributeTypeCodes = implode(', ', array_keys($this->attributeTypeRegistry->getTypes()));
                $m = "The attribute {$this->code} has an unknown type '{$configuration['type']}'.\n";
                $m .= "Available types are: {$attributeTypeCodes}";
                throw new AttributeConfigurationException($m, 0, $e);
            }
            if ($this->type && $this->type->getDatabaseType() !== $newType->getDatabaseType()) {
                $e = "The attribute '{$this->code}' cannot be overridden with a new attribute type that don't match ";
                $e .= "the database type '{$this->type->getDatabaseType()}'";
                throw new AttributeConfigurationException($e);
            }
            $this->type = $newType;
            unset($configuration['type']);
        }

        $refl = new \ReflectionClass($this);
        foreach ($configuration as $key => $value) {
            $key = \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $key))));
            if (!$refl->hasProperty($key)) {
                throw new AttributeConfigurationException(
                    "The attribute {$this->code} has an invalid configuration for option '{$key}'"
                );
            }
            /** @noinspection PhpVariableVariableInspection */
            $this->$key = $value;
        }

        $this->type->setAttributeDefaults($this); // Allow attribute type service to configure attribute

        $this->checkConflicts();
    }

    /**
     * Remove service references before serializing
     *
     * @throws \ReflectionException
     *
     * @return array
     */
    public function __sleep()
    {
        $this->fallbackLabel = $this->getLabel();

        return SleepUtility::sleep(__CLASS__, ['translator', 'attributeTypeRegistry']);
    }

    /**
     * Custom debug info
     *
     * @return array
     */
    public function __debugInfo()
    {
        return DebugInfoUtility::debugInfo(
            $this,
            [
                Caster::PREFIX_PROTECTED.'translator',
                Caster::PREFIX_PROTECTED.'attributeTypeRegistry',
                Caster::PREFIX_PROTECTED.'fallbackLabel',
            ]
        );
    }

    /**
     * @throws AttributeConfigurationException
     */
    protected function checkConflicts()
    {
        $default = $this->getDefault();
        if (\in_array($this->getCode(), AttributeInterface::FORBIDDEN_ATTRIBUTE_CODES, true)) {
            throw new \UnexpectedValueException(
                "Attribute code '{$this->getCode()}' is not allowed"
            );
        }
        if ($this->isCollection()) {
            if ($this->isUnique()) {
                throw new AttributeConfigurationException(
                    "Attribute {$this->getCode()} cannot be a collection and unique at the same time"
                );
            }
            if (null !== $default && !(\is_array($default) || $default instanceof \Traversable)) {
                $e = "Attribute {$this->getCode()} is a collection and therefore should have an array of values for";
                $e .= ' the default option';
                throw new AttributeConfigurationException($e);
            }
        } elseif ($this->isMultiple()) {
            throw new AttributeConfigurationException(
                "Attribute {$this->getCode()} cannot be multiple and not a collection"
            );
        }

        if (null !== $default && ($this->getType()->isRelation() || $this->getType()->isEmbedded())) {
            $e = "Attribute {$this->getCode()} is a relation to an other entity, it doesn't support default values";
            $e .= ' in configuration';
            throw new AttributeConfigurationException($e);
        }
    }
}
