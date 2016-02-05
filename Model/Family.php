<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Configuration\AttributeConfigurationHandler;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Entity\Value;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use UnexpectedValueException;

class Family implements FamilyInterface
{
    use TranslatableTrait;

    /** @var string */
    protected $code;

    /** @var string */
    protected $label;

    /** @var Attribute */
    protected $attributeAsLabel;

    /** @var Attribute[] */
    protected $attributes = [];

    /** @var PermissionMapInterface[] */
    protected $permissions = [];

    /** @var Family */
    protected $parent;

    /** @var bool */
    protected $instantiable;

    /** @var Family[] */
    protected $children = [];

    /** @var string */
    protected $dataClass;

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
        $this->dataClass = $config['data_class'];
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
        $this->label = $config['label'];
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
        $this->valueClass = $parent->getValueClass();
        $this->dataClass = $parent->getDataClass();
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
            $codes += $child->getMatchingCodes();
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
     * @return string
     */
    public function getDataClass()
    {
        return $this->dataClass;
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

    /**
     * @return Data
     */
    public function createData()
    {
        $dataClass = $this->getDataClass();
        return new $dataClass($this);
    }
}
