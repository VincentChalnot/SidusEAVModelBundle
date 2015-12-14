<?php

namespace Sidus\EAVModelBundle\Form;

use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class DataType extends AbstractType
{
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    protected $dataClass;

    /**
     * @param $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Data $data */
        $data = $builder->getData();
        if ($data && $data->getFamilyCode()) {
            $this->buildValuesForm($builder, $options);
            // $this->buildDataForm($builder, $options);
        } else {
            $this->buildCreateForm($builder, $options);
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildCreateForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        foreach ($this->familyConfigurationHandler->getFamilies() as $family) {
            $choices[$family->getCode()] = $this->translator->trans($family->getCode());
        }

        $builder->add('familyCode', 'choice', [
            'choices' => $choices,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildDataForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isArchived', 'checkbox', [
                'required' => false,
            ]);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildValuesForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Data $data */
        $data = $builder->getData();
        $family = $this->familyConfigurationHandler->getFamily($data->getFamilyCode());
        foreach ($family->getAttributes() as $attribute) {
            $attributeType = $attribute->getType();
            $label = $this->getFieldLabel($family, $attribute);

            if ($attribute->isMultiple()) {
                $formOptions = $attribute->getFormOptions();
                $formOptions['label'] = false;
                $builder->add($attribute->getCode(), 'sidus_bootstrap_collection', [
                    'label' => $label,
                    'type' => $attributeType->getFormType(),
                    'options' => $formOptions,
                    'allow_add' => true,
                    'allow_delete' => true,
                ]);
            } else {
                $formOptions = array_merge(['label' => $label], $attribute->getFormOptions());
                $builder->add($attribute->getCode(), $attributeType->getFormType(), $formOptions);
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_data';
    }

    /**
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     */
    public function setFamilyConfiguration(FamilyConfigurationHandler $familyConfigurationHandler)
    {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Use label from formOptions or use translation or automatically create human readable one
     *
     * @param FamilyInterface $family
     * @param AttributeInterface $attribute
     * @return string
     */
    protected function getFieldLabel(FamilyInterface $family, AttributeInterface $attribute)
    {
        $transKey = "{$family->getCode()}.attribute.{$attribute->getCode()}";
        $label = $this->translator->trans($transKey . '.label');
        if ($label === $transKey . '.label') {
            $label = ucfirst(preg_replace('/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]|[0-9]{1,}/', ' $0', $attribute->getCode()));
        }
        return $label;
    }
}
