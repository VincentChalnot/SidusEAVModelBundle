<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Configuration\AttributeConfigurationHandler;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Entity\Value;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnexpectedValueException;

class Family implements FamilyInterface
{
    /** @var string */
    protected $code;

    /** @var Attribute */
    protected $attributeAsLabel;

    /** @var Attribute[] */
    protected $attributes = [];

    /** @var bool */
    protected $versionable = false;

    /** @var PermissionMapInterface[] */
    protected $permissions = [];

    /** @var ValidatorInterface[] */
    protected $validationRules = [];

    /** @var Family */
    protected $parent;

    /** @var bool */
    protected $instantiable;

    /** @var Family[] */
    protected $children = [];

    /** @var string */
    protected $valueClass;

    /**
     * @param string $code
     * @param AttributeConfigurationHandler $attributeConfigurationHandler
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     * @param array $config
     * @throws MissingFamilyException
     * @throws UnexpectedValueException
     */
    public function __construct($code, AttributeConfigurationHandler $attributeConfigurationHandler, FamilyConfigurationHandler $familyConfigurationHandler, array $config = null)
    {
        $this->code = $code;
        $this->valueClass = $config['value_class'];
        if (!empty($config['parent'])) {
            $this->parent = $familyConfigurationHandler->getFamily($config['parent']);
            $this->copyFromFamily($this->parent);
        }
        foreach ($config['attributes'] as $code) {
            $this->attributes[$code] = $attributeConfigurationHandler->getAttribute($code);
        }
        if (!empty($config['attributeAsLabel'])) {
            $this->attributeAsLabel = $attributeConfigurationHandler->getAttribute($config['attributeAsLabel']);
        }
        $this->instantiable = $config['instantiable'];
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
     * @return Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function addAttribute(AttributeInterface $attribute)
    {
        $this->attributes[$attribute->getCode()] = $attribute;
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
     * @return \Symfony\Component\Security\Acl\Permission\PermissionMapInterface[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param \Symfony\Component\Security\Acl\Permission\PermissionMapInterface[] $permissions
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface[]
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface[] $validationRules
     */
    public function setValidationRules(array $validationRules)
    {
        $this->validationRules = $validationRules;
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
        return $this->children;
    }

    /**
     * @param FamilyInterface $child
     * @throws UnexpectedValueException
     */
    public function addChild(FamilyInterface $child)
    {
        if ($child->getParent() && $child->getParent()->getCode() === $this->getCode()) {
            $this->children[$child->getCode()] = $child;
        } else {
            throw new UnexpectedValueException("Child must have it's parent set to the current family");
        }
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    protected function copyFromFamily(FamilyInterface $parent)
    {
        foreach ($parent->getAttributes() as $attribute) {
            $this->addAttribute($attribute);
        }
        $this->attributeAsLabel = $parent->getAttributeAsLabel();
        // @todo for other properties
    }

    /**
     * @param $code
     * @return AttributeInterface
     * @throws UnexpectedValueException
     */
    public function getAttribute($code)
    {
        if (empty($this->attributes[$code])) {
            throw new UnexpectedValueException("Unknown attribute {$code} in family {$this->code}");
        }
        return $this->attributes[$code];
    }

    /**
     * @param $code
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

    public function __toString()
    {
        return 'sidus.family.' . $this->getCode();
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
     * @param Data $data
     * @param AttributeInterface $attribute
     * @return Value
     */
    public function createValue(Data $data, AttributeInterface $attribute)
    {
        $valueClass = $this->getValueClass();
        $value = new $valueClass($data, $attribute);
        $data->addValue($value);
        return $value;
    }
}
