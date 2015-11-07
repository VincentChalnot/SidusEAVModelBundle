<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Configuration\AttributeTypeConfigurationHandler;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Attribute implements AttributeInterface
{
    /** @var string */
    protected $code;

    /** @var AttributeType */
    protected $type;

    /** @var array */
    protected $formOptions = [];

    /** @var array */
    protected $viewOptions = [];

    /** @var bool */
    protected $versionable = false;

    /** @var PermissionMapInterface[] */
    protected $permissions = [];

    /** @var bool */
    protected $required = false;

    /** @var bool */
    protected $unique = false;

    /** @var ValidatorInterface[] */
    protected $validationRules = [];

    /** @var bool */
    protected $multiple = false;

    /** @var bool */
    protected $scopable = false;

    /** @var bool */
    protected $translatable = false;

    /** @var bool */
    protected $countrySpecific = false;

    /** @var bool */
    protected $localizable = false;

    /**
     * @param string $code
     * @param AttributeTypeConfigurationHandler $attributeTypeConfigurationHandler
     * @param array $configuration
     */
    public function __construct($code, AttributeTypeConfigurationHandler $attributeTypeConfigurationHandler, array $configuration = null)
    {
        $this->code = $code;
        $this->type = $attributeTypeConfigurationHandler->getType($configuration['type']);
        unset($configuration['type']);
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($configuration as $key => $value) {
            $accessor->setValue($this, $key, $value);
        }
        if ($this->localizable) {
            $this->countrySpecific = true;
            $this->translatable = true;
        }
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
    public function getFormOptions()
    {
        $options = array_merge([
            'required' => $this->required,
        ], $this->formOptions);
        return $options;
    }

    /**
     * @param array $formOptions
     */
    public function setFormOptions(array $formOptions)
    {
        $this->formOptions = $formOptions;
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
    public function isVersionable()
    {
        return $this->versionable;
    }

    /**
     * @param boolean $versionable
     */
    public function setVersionable($versionable)
    {
        $this->versionable = $versionable;
    }

    /**
     * @return PermissionMapInterface[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param PermissionMapInterface[] $permissions
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
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
     * @return ValidatorInterface[]
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * @param ValidatorInterface[] $validationRules
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
     * @return boolean
     */
    public function isScopable()
    {
        return $this->scopable;
    }

    /**
     * @param boolean $scopable
     */
    public function setScopable($scopable)
    {
        $this->scopable = $scopable;
    }

    /**
     * @return boolean
     */
    public function isTranslatable()
    {
        return $this->translatable;
    }

    /**
     * @param boolean $translatable
     */
    public function setTranslatable($translatable)
    {
        $this->translatable = $translatable;
    }

    /**
     * @return boolean
     */
    public function isCountrySpecific()
    {
        return $this->countrySpecific;
    }

    /**
     * @param boolean $countrySpecific
     */
    public function setCountrySpecific($countrySpecific)
    {
        $this->countrySpecific = $countrySpecific;
    }

    /**
     * @return boolean
     */
    public function isLocalizable()
    {
        return $this->localizable;
    }

    /**
     * @param boolean $localizable
     */
    public function setLocalizable($localizable)
    {
        $this->localizable = $localizable;
    }
}
