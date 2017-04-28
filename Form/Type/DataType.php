<?php

namespace Sidus\EAVModelBundle\Form\Type;

use Sidus\EAVModelBundle\Form\AttributeFormBuilderInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base form used for data edition
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataType extends AbstractType
{
    /** @var AttributeFormBuilderInterface */
    protected $attributeFormBuilder;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var string */
    protected $dataClass;

    /**
     * @param AttributeFormBuilderInterface $attributeFormBuilder
     * @param FamilyRegistry                $familyRegistry
     * @param string                        $dataClass
     */
    public function __construct(
        AttributeFormBuilderInterface $attributeFormBuilder,
        FamilyRegistry $familyRegistry,
        $dataClass
    ) {
        $this->attributeFormBuilder = $attributeFormBuilder;
        $this->familyRegistry = $familyRegistry;
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildValuesForm($builder, $options);
        $this->buildDataForm($builder, $options);

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof DataInterface) {
                    $data->setUpdatedAt(new \DateTime());
                }
            }
        );
    }

    /**
     * For additional fields in data form that are not linked to EAV model
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildDataForm(
        FormBuilderInterface $builder,
        array $options = []
    ) {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Exception
     */
    public function buildValuesForm(FormBuilderInterface $builder, array $options = [])
    {
        /** @var FamilyInterface $family */
        $family = $options['family'];

        if ($options['fields_config']) {
            /** @var array $fieldsConfig */
            $fieldsConfig = $options['fields_config'];
            foreach ($fieldsConfig as $attributeCode => $fieldConfig) {
                $attribute = $family->getAttribute($attributeCode);
                $this->attributeFormBuilder->addAttribute($builder, $attribute, $fieldConfig);
            }
        } else {
            foreach ($family->getAttributes() as $attribute) {
                $fieldConfig = [];
                $this->attributeFormBuilder->addAttribute($builder, $attribute, $fieldConfig);
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     * @throws MissingFamilyException
     * @throws \UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\WrongFamilyException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'fields_config' => null,
            ]
        );
        $resolver->setAllowedTypes('fields_config', ['NULL', 'array']);
        $resolver->setRequired(
            [
                'family',
            ]
        );
        $resolver->setNormalizer(
            'family',
            function (Options $options, $value) {
                if ($value instanceof FamilyInterface) {
                    return $value;
                }

                return $this->familyRegistry->getFamily($value);
            }
        );
        $resolver->setNormalizer(
            'empty_data',
            function (Options $options, $value) {
                if (null !== $value) {
                    return $value;
                }
                if ($options['family'] instanceof FamilyInterface) {
                    return $options['family']->createData();
                }

                return $value;
            }
        );
        $resolver->setNormalizer(
            'data_class',
            function (Options $options, $value) {
                if ($options['family'] instanceof FamilyInterface) {
                    return $options['family']->getDataClass();
                }

                return $value;
            }
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_data';
    }
}
