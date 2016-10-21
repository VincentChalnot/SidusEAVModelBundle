<?php

namespace Sidus\EAVModelBundle\Form\Type;

use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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
    use TranslatableTrait;

    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var string */
    protected $collectionType;

    /** @var string */
    protected $dataClass;

    /**
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     * @param string                     $dataClass
     * @param string                     $collectionType
     */
    public function __construct(
        FamilyConfigurationHandler $familyConfigurationHandler,
        $dataClass,
        $collectionType = 'collection'
    ) {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
        $this->dataClass = $dataClass;
        $this->collectionType = $collectionType;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            /** @var DataInterface $data */
            $data = $event->getData();

            if ($data) {
                $family = $data->getFamily();
            } else {
                $family = $options['family'];
            }

            if ($family) {
                $this->buildValuesForm($form, $family, $data, $options);
                $this->buildDataForm($form, $family, $data, $options);
            } else {
                $this->buildCreateForm($form, $options);
            }
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if ($data instanceof DataInterface) {
                $data->setUpdatedAt(new \DateTime());
            }
        });
    }

    /**
     * @param FormInterface $form
     * @param array         $options
     *
     * @throws \Exception
     */
    public function buildCreateForm(FormInterface $form, array $options)
    {
        $form->add('family', 'sidus_family_selector');
    }

    /**
     * For additional fields in data form that are not linked to EAV model
     *
     * @param FormInterface   $form
     * @param FamilyInterface $family
     * @param DataInterface   $data
     * @param array           $options
     */
    public function buildDataForm(
        FormInterface $form,
        FamilyInterface $family,
        DataInterface $data = null,
        array $options = []
    ) {

    }

    /**
     * @param FormInterface   $form
     * @param FamilyInterface $family
     * @param DataInterface   $data
     * @param array           $options
     *
     * @throws \Exception
     */
    public function buildValuesForm(
        FormInterface $form,
        FamilyInterface $family,
        DataInterface $data = null,
        array $options = []
    ) {
        foreach ($family->getAttributes() as $attribute) {
            $this->addAttribute($form, $attribute, $family, $data, $options);
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     * @throws MissingFamilyException
     * @throws \UnexpectedValueException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'family' => null,
            'data_class' => $this->dataClass,
        ]);
        $resolver->setNormalizer('family', function (Options $options, $value) {
            if ($value === null) {
                return null;
            }
            if ($value instanceof FamilyInterface) {
                return $value;
            }

            return $this->familyConfigurationHandler->getFamily($value);
        });
        $resolver->setNormalizer('empty_data', function (Options $options, $value) {
            if ($options['family'] instanceof FamilyInterface) {
                return $options['family']->createData();
            }

            return $value;
        });
        $resolver->setNormalizer('data_class', function (Options $options, $value) {
            if ($options['family'] instanceof FamilyInterface) {
                return $options['family']->getDataClass();
            }

            return $value;
        });
        $resolver->setNormalizer('data', function (Options $options, $value) {
            if (null === $value) {
                return null;
            }
            if (!$value instanceof DataInterface) {
                throw new \UnexpectedValueException("The 'data' option should be a DataInterface");
            }
            if ($options['family'] instanceof FamilyInterface) {
                $dataClass = $options['family']->getDataClass();
                if (!is_a($value, $dataClass)) {
                    throw new \UnexpectedValueException("The 'data' option should be a {$dataClass}");
                }
            }

            return $value;
        });
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_data';
    }

    /**
     * @param FormInterface      $form
     * @param AttributeInterface $attribute
     * @param FamilyInterface    $family
     * @param DataInterface|null $data
     * @param array              $options
     *
     * @throws \Exception
     */
    protected function addAttribute(
        FormInterface $form,
        AttributeInterface $attribute,
        FamilyInterface $family,
        DataInterface $data = null,
        array $options = []
    ) {
        if ($attribute->getOption('hidden')) {
            return;
        }
        if ($attribute->isMultiple() && $attribute->isCollection()) {
            $this->addMultipleAttribute($form, $attribute, $family, $data, $options);
        } else {
            $this->addSingleAttribute($form, $attribute, $family, $data, $options);
        }
    }

    /**
     * @param FormInterface      $form
     * @param AttributeInterface $attribute
     * @param FamilyInterface    $family
     * @param DataInterface      $data
     * @param array              $options
     *
     * @throws \Exception
     */
    protected function addSingleAttribute(
        FormInterface $form,
        AttributeInterface $attribute,
        FamilyInterface $family,
        DataInterface $data = null,
        array $options = []
    ) {
        $label = $this->getFieldLabel($family, $attribute);
        $formOptions = $attribute->getFormOptions($data);

        $formOptions = array_merge(['label' => $label], $formOptions);
        unset($formOptions['collection_options']); // Ignoring collection_options if set
        $form->add($attribute->getCode(), $attribute->getType()->getFormType(), $formOptions);
    }

    /**
     * @param FormInterface      $form
     * @param AttributeInterface $attribute
     * @param FamilyInterface    $family
     * @param DataInterface      $data
     * @param array              $options
     *
     * @throws \Exception
     */
    protected function addMultipleAttribute(
        FormInterface $form,
        AttributeInterface $attribute,
        FamilyInterface $family,
        DataInterface $data = null,
        array $options = []
    ) {
        $label = $this->getFieldLabel($family, $attribute);
        $formOptions = $attribute->getFormOptions($data);

        $formOptions['label'] = false; // Removing label
        $collectionOptions = [
            'label' => $label,
            'type' => $attribute->getType()->getFormType(),
            'entry_options' => $formOptions,
            'allow_add' => true,
            'allow_delete' => true,
            'required' => $attribute->isRequired(),
            'sortable' => false,
            'prototype_name' => '__'.$attribute->getCode().'__',
        ];
        if (!empty($formOptions['collection_options'])) {
            $collectionOptions = array_merge($collectionOptions, $formOptions['collection_options']);
        }
        unset($collectionOptions['entry_options']['collection_options']);
        $form->add($attribute->getCode(), $this->collectionType, $collectionOptions);
    }

    /**
     * Use label from formOptions or use translation or automatically create human readable one
     *
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getFieldLabel(FamilyInterface $family, AttributeInterface $attribute)
    {
        $tId = "eav.{$family->getCode()}.attribute.{$attribute->getCode()}.label";

        return $this->tryTranslate($tId, [], ucfirst($attribute));
    }
}
