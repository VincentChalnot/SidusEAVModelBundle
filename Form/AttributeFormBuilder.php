<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Form;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\BaseBundle\Translator\TranslatableTrait;
use Sidus\BaseBundle\Validator\Mapping\Loader\BaseLoader;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * Helper to append an attribute to a form type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
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
        $resolver = new OptionsResolver();
        $this->configureOptions($attribute, $resolver);
        $options = $resolver->resolve($options);

        if ($options['hidden']) {
            return;
        }

        $builder = $this->resolveBuilder($builder, $attribute, $options);

        // The 'multiple' option triggers the usage of the Collection form type
        if ($options['multiple']) {
            // This means that a specific attribute can be a collection of data but might NOT be "multiple" in a sense
            // that it will not be edited as a "collection" form type.
            // Be wary of the vocabulary here
            $this->addMultipleAttribute($builder, $attribute, $options);
        } else {
            $this->addSingleAttribute($builder, $attribute, $options);
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $options
     *
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     *
     * @return FormBuilderInterface
     */
    protected function resolveBuilder(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $options = []
    ) {
        $group = $attribute->getGroup();
        if (null === $group || $options['ignore_group']) {
            return $builder;
        }

        $groupPath = explode('.', $group);

        $subBuilder = $builder;
        foreach ($groupPath as $level => $groupCode) {
            $subBuilder = $this->buildFieldset($subBuilder, $attribute, $groupPath, $level, $options);
        }

        return $subBuilder;
    }

    /**
     * @param FormBuilderInterface $parentBuilder
     * @param AttributeInterface   $attribute
     * @param array                $groupPath
     * @param int                  $level
     * @param array                $options
     *
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     *
     * @return FormBuilderInterface
     */
    protected function buildFieldset(
        FormBuilderInterface $parentBuilder,
        AttributeInterface $attribute,
        array $groupPath,
        $level,
        array $options = []
    ) {
        $fieldsetCode = '__'.$groupPath[$level];
        if ($parentBuilder->has($fieldsetCode)) {
            return $parentBuilder->get($fieldsetCode);
        }

        $fieldsetOptions = [
            'label' => $this->getGroupLabel($attribute->getFamily(), $groupPath, $level),
            'inherit_data' => true,
        ];

        $fieldsetPath = $this->getFieldsetPath($groupPath, $level);
        if (isset($options['fieldset_options'][$fieldsetPath])) {
            $fieldsetOptions = array_merge($fieldsetOptions, $options['fieldset_options'][$fieldsetPath]);
        }

        $parentBuilder->add($fieldsetCode, FormType::class, $fieldsetOptions);

        return $parentBuilder->get($fieldsetCode);
    }

    /**
     * Use label from formOptions or use translation or automatically create human readable one
     *
     * @param FamilyInterface $family
     * @param array           $groupPath
     * @param int             $level
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    protected function getGroupLabel(FamilyInterface $family, array $groupPath, $level)
    {
        $fallBack = isset($groupPath[$level]) ? $groupPath[$level] : null;
        $fieldsetPath = $this->getFieldsetPath($groupPath, $level);
        $transKeys = [
            "eav.family.{$family->getCode()}.group.{$fieldsetPath}.label",
            "eav.group.{$fieldsetPath}.label",
        ];

        return ucfirst($this->tryTranslate($transKeys, [], $fallBack));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $options
     *
     * @throws \Exception
     */
    protected function addSingleAttribute(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $options = []
    ) {
        $formOptions = $options['form_options'];
        unset($formOptions['collection_options']); // Ignoring collection_options if set

        $builder->add($attribute->getCode(), $options['form_type'], $formOptions);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param AttributeInterface   $attribute
     * @param array                $options
     *
     * @throws \Exception
     */
    protected function addMultipleAttribute(
        FormBuilderInterface $builder,
        AttributeInterface $attribute,
        array $options = []
    ) {
        $formOptions = $options['form_options'];

        $disabled = array_key_exists('disabled', $formOptions) ? !$formOptions['disabled'] : true;
        $label = $formOptions['label']; // Keeping configured label
        $formOptions['label'] = false; // Removing label from entry_options
        $collectionOptions = [
            'label' => $label,
            'translation_domain' => $formOptions['translation_domain'],
            'translate_label' => $formOptions['translate_label'],
            'entry_type' => $options['form_type'],
            'entry_options' => $formOptions,
            'allow_add' => $disabled,
            'allow_delete' => $disabled,
            'required' => $formOptions['required'],
            'prototype_name' => '__'.$attribute->getCode().'__',
        ];
        if (!empty($formOptions['collection_options'])) {
            $collectionOptions = array_merge($collectionOptions, $formOptions['collection_options']);
        }
        unset($collectionOptions['entry_options']['collection_options']);

        $builder->add($attribute->getCode(), $options['collection_type'], $collectionOptions);
    }

    /**
     * @param AttributeInterface $attribute
     * @param OptionsResolver    $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\Validator\Exception\MappingException
     * @throws \UnexpectedValueException
     */
    protected function configureOptions(AttributeInterface $attribute, OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => ucfirst($attribute),
                'form_type' => $attribute->getFormType(),
                'hidden' => $attribute->getOption('hidden', false),
                'merge_form_options' => true,
                'multiple' => $attribute->isMultiple(),
                'form_options' => [],
                'validation_rules' => null,
                'collection_type' => $this->collectionType,
                'ignore_group' => false,
                'fieldset_options' => [],
            ]
        );
        $resolver->setAllowedTypes('label', ['string']);
        $resolver->setAllowedTypes('form_type', ['string']);
        $resolver->setAllowedTypes('hidden', ['boolean']);
        $resolver->setAllowedTypes('merge_form_options', ['boolean']);
        $resolver->setAllowedTypes('multiple', ['boolean']);
        $resolver->setAllowedTypes('form_options', ['array']);
        $resolver->setAllowedTypes('validation_rules', ['NULL', 'array']);
        $resolver->setAllowedTypes('collection_type', ['string']);
        $resolver->setAllowedTypes('ignore_group', ['boolean']);
        $resolver->setAllowedTypes('fieldset_options', ['array']);

        $resolver->setNormalizer(
            'form_options',
            function (Options $options, $value) use ($attribute) {
                $formOptions = $value;
                if ($options['merge_form_options']) {
                    $formOptions = array_merge($attribute->getFormOptions(), $value);
                }

                // If set, override constraints by configured validation rules
                if (null !== $options['validation_rules']) {
                    $formOptions['constraints'] = $this->parseValidationRules($options['validation_rules']);
                }

                $defaultOptions = [
                    'label' => $options['label'],
                    'translation_domain' => null,
                    'translate_label' => false,
                    'required' => $attribute->isRequired(),
                ];

                return array_merge($defaultOptions, $formOptions);
            }
        );
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
        $loader = new BaseLoader();

        return $loader->loadCustomConstraints($validationRules);
    }

    /**
     * @param array $groupPath
     * @param int   $level
     *
     * @return string
     */
    protected function getFieldsetPath(array $groupPath, int $level)
    {
        return implode('.', array_splice($groupPath, 0, $level + 1));
    }
}
