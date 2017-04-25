<?php

namespace Sidus\EAVModelBundle\Form;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Sidus\EAVModelBundle\Validator\Mapping\Loader\BaseLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Helper to append an attribute to a form type
 */
class AttributeFormBuilder implements AttributeFormBuilderInterface
{
    use TranslatableTrait;

    /** @var string */
    protected $collectionType;

    /**
     * @param string $collectionType
     */
    public function __construct($collectionType)
    {
        $this->collectionType = $collectionType;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $options
     *
     * @throws \Exception
     */
    public function addAttribute(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $options = []
    ) {
        if ($attribute->getOption('hidden')) { // Also search $options ?
            return;
        }

        $formOptions = [];
        if (array_key_exists('form_options', $options)) {
            $formOptions = $options['form_options'];
        }

        if (array_key_exists('validation_rules', $options)) {
            $formOptions['constraints'] = $this->parseValidationRules($options['validation_rules']);
        }

        // The 'multiple' option triggers the usage of the Collection form type
        if ($attribute->isMultiple()) {
            // This means that a specific attribute can be a collection of data but might NOT be "multiple" in a sense
            // that it will not be edited as a "collection" form type.
            // Be wary of the vocabulary here
            $this->addMultipleAttribute($builder, $attribute, $formOptions);
        } else {
            $this->addSingleAttribute($builder, $attribute, $formOptions);
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $formOptions
     *
     * @throws \Exception
     */
    protected function addSingleAttribute(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $formOptions = []
    ) {
        $formOptions = array_merge(['label' => ucfirst($attribute)], $formOptions, $attribute->getFormOptions());
        unset($formOptions['collection_options']); // Ignoring collection_options if set

        $builder->add($attribute->getCode(), $attribute->getFormType(), $formOptions);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $formOptions
     *
     * @throws \Exception
     */
    protected function addMultipleAttribute(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $formOptions = []
    ) {
        $formOptions = array_merge($attribute->getFormOptions(), $formOptions); // Invert priority ?

        $disabled = array_key_exists('disabled', $formOptions) ? !$formOptions['disabled'] : true;
        $formOptions['label'] = false; // Removing label
        $collectionOptions = [
            'label' => ucfirst($attribute),
            'entry_type' => $attribute->getFormType(),
            'entry_options' => $formOptions,
            'allow_add' => $disabled,
            'allow_delete' => $disabled,
            'required' => $attribute->isRequired(),
            'sortable' => false,
            'prototype_name' => '__'.$attribute->getCode().'__',
        ];
        if (!empty($formOptions['collection_options'])) {
            $collectionOptions = array_merge($collectionOptions, $formOptions['collection_options']);
        }
        unset($collectionOptions['entry_options']['collection_options']);

        $builder->add($attribute->getCode(), $this->collectionType, $collectionOptions);
    }

    /**
     * @param array $validationRules
     *
     * @throws \Symfony\Component\Validator\Exception\MappingException
     * @throws \UnexpectedValueException
     *
     * @return Constraint[]
     */
    protected function parseValidationRules(array $validationRules)
    {
        $constraints = [];
        $loader = new BaseLoader();
        foreach ($validationRules as $validationRule) {
            if (!is_array($validationRule)) {
                throw new \UnexpectedValueException('Invalid validation rules definition');
            }
            /** @var array $validationRule */
            foreach ($validationRule as $item => $options) {
                $constraints[] = $loader->newConstraint($item, $options);
            }
        }

        return $constraints;
    }
}
