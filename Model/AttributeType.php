<?php

namespace Sidus\EAVModelBundle\Model;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Type of attribute like string, integer, etc.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeType implements AttributeTypeInterface
{
    /**
     * Not exactly scalar types as it includes also Date/Time types but exclude all relationships
     *
     * @var array
     */
    protected static $scalarDatabaseTypes = [
        'boolValue',
        'integerValue',
        'decimalValue',
        'dateValue',
        'datetimeValue',
        'stringValue',
        'textValue',
    ];

    /** @var string */
    protected $code;

    /** @var string */
    protected $databaseType;

    /** @var string */
    protected $formType;

    /** @var bool */
    protected $isEmbedded = false;

    /** @var bool */
    protected $isRelation = false;

    /** @var array */
    protected $formOptions = [];

    /**
     * AttributeType constructor.
     *
     * @param string $code
     * @param string $databaseType
     * @param string $formType
     * @param array  $formOptions
     */
    public function __construct($code, $databaseType, $formType, array $formOptions = [])
    {
        $this->code = $code;
        $this->databaseType = $databaseType;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
        if (!in_array($this->databaseType, $this::$scalarDatabaseTypes, true)) {
            $this->isRelation = true;
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
     * @return string
     */
    public function getDatabaseType()
    {
        return $this->databaseType;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        $formTypes = [
            'text' => TextType::class,
            'number' => NumberType::class,
            'choice' => ChoiceType::class,
        ];
        return $formTypes[$this->formType];
    }

    /**
     * @return boolean
     */
    public function isEmbedded()
    {
        return $this->isEmbedded;
    }

    /**
     * @param boolean $isEmbedded
     */
    public function setEmbedded($isEmbedded)
    {
        $this->isEmbedded = $isEmbedded;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
    }

    /**
     * @param mixed $data
     * @return array
     */
    public function getFormOptions($data)
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     */
    public function setFormOptions($formOptions)
    {
        $this->formOptions = $formOptions;
    }

    /**
     * @return bool
     */
    public function isRelation()
    {
        return $this->isRelation;
    }

    /**
     * @param boolean $isRelation
     */
    public function setRelation($isRelation)
    {
        $this->isRelation = $isRelation;
    }
}
