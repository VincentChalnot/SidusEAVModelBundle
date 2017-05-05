<?php

namespace Sidus\EAVModelBundle\Registry;

use Sidus\EAVModelBundle\Exception\AttributeConfigurationException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Translation\TranslatorInterface;
use UnexpectedValueException;

/**
 * Container for all global attributes.
 *
 * @internal Don't use this service to fetch attributes, use the families instead
 *
 * @author   Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeRegistry
{
    /** @var string */
    protected $attributeClass;

    /** @var array */
    protected $globalContextMask;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var AttributeInterface[] */
    protected $attributes = [];

    /** @var array */
    protected static $reservedCodes = [
        'id',
        'identifier',
        'parent',
        'children',
        'values',
        'value',
        'valueData',
        'valuesData',
        'refererValues',
        'createdAt',
        'updatedAt',
        'family',
        'familyCode',
        'currentContext',
        'empty',
    ];

    /**
     * @param string                $attributeClass
     * @param array                 $globalContextMask
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param TranslatorInterface   $translator
     */
    public function __construct(
        $attributeClass,
        array $globalContextMask,
        AttributeTypeRegistry $attributeTypeRegistry,
        TranslatorInterface $translator
    ) {
        $this->attributeClass = $attributeClass;
        $this->globalContextMask = $globalContextMask;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->translator = $translator;
    }

    /**
     * @param array $globalConfig
     *
     * @throws \UnexpectedValueException
     */
    public function parseGlobalConfig(array $globalConfig)
    {
        foreach ($globalConfig as $code => $configuration) {
            $attribute = $this->createAttribute($code, $configuration);
            $this->addAttribute($attribute);
        }
    }

    /**
     * @param string $code
     * @param array  $attributeConfiguration
     *
     * @return AttributeInterface
     */
    public function createAttribute($code, array $attributeConfiguration = [])
    {
        $attributeConfiguration['context_mask'] = array_merge(
            $this->globalContextMask,
            $attributeConfiguration['context_mask']
        );

        $attributeClass = $this->attributeClass;
        /** @var AttributeInterface $attribute */
        $attribute = new $attributeClass($code, $this->attributeTypeRegistry, $attributeConfiguration);
        if (method_exists($attribute, 'setTranslator')) {
            $attribute->setTranslator($this->translator);
        }

        return $attribute;
    }

    /**
     * @return AttributeInterface[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $code
     *
     * @throws UnexpectedValueException
     *
     * @return AttributeInterface
     */
    public function getAttribute($code)
    {
        if (!$this->hasAttribute($code)) {
            throw new UnexpectedValueException("No attribute with code : {$code}");
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
        return array_key_exists($code, $this->attributes);
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @throws \UnexpectedValueException
     */
    protected function addAttribute(AttributeInterface $attribute)
    {
        if (in_array($attribute->getCode(), static::$reservedCodes, true)) {
            throw new AttributeConfigurationException("Attribute code '{$attribute->getCode()}' is a reserved code");
        }
        $this->attributes[$attribute->getCode()] = $attribute;
    }
}
