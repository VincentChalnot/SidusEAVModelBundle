<?php

namespace Sidus\EAVModelBundle\Form\Type;

use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataType extends AbstractType
{
    use TranslatableTrait;

    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $collectionType;

    /**
     * @param string $dataClass
     * @param string $collectionType
     */
    public function __construct($dataClass, $collectionType = 'collection')
    {
        $this->dataClass = $dataClass;
        $this->collectionType = $collectionType;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            /** @var Data $data */
            $data = $event->getData();

            if ($data && $data->getFamily()) {
                $this->buildValuesForm($data, $form, $options);
                $this->buildDataForm($data, $form, $options);
            } else {
                $this->buildCreateForm($form, $options);
            }
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event){
            /** @var Data $data */
            $data = $event->getData();
            if ($data) {
                $data->setUpdatedAt(new \DateTime());
            }
        });
    }

    /**
     * @param FormInterface $form
     * @param array $options
     * @throws \Exception
     */
    public function buildCreateForm(FormInterface $form, array $options)
    {
        $choices = [];
        foreach ($this->familyConfigurationHandler->getFamilies() as $family) {
            $choices[$family->getCode()] = $this->translator->trans((string) $family);
        }

        $form->add('family', 'choice', [
            'choices' => $choices,
        ]);
    }

    /**
     * For additional fields in data form that are not linked to EAV model
     *
     * @param Data $data
     * @param FormInterface $form
     * @param array $options
     */
    public function buildDataForm(Data $data, FormInterface $form, array $options)
    {

    }

    /**
     * @param Data $data
     * @param FormInterface $form
     * @param array $options
     * @throws \Exception
     */
    public function buildValuesForm(Data $data, FormInterface $form, array $options)
    {
        $family = $data->getFamily();
        foreach ($family->getAttributes() as $attribute) {
            $this->addAttribute($form, $attribute, $family);
        }
    }

    /**
     * @param FormInterface $form
     * @param AttributeInterface $attribute
     * @param FamilyInterface $family
     * @throws \Exception
     */
    protected function addAttribute(FormInterface $form, AttributeInterface $attribute, FamilyInterface $family)
    {
        $attributeType = $attribute->getType();
        $label = $this->getFieldLabel($family, $attribute);

        if ($attribute->isMultiple()) {
            $formOptions = $attribute->getFormOptions();
            $formOptions['label'] = false;
            $form->add($attribute->getCode(), $this->collectionType, [
                'label' => $label,
                'type' => $attributeType->getFormType(),
                'options' => $formOptions,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ]);
        } else {
            $formOptions = array_merge(['label' => $label], $attribute->getFormOptions());
            $form->add($attribute->getCode(), $attributeType->getFormType(), $formOptions);
        }
    }

    /**
     * @param OptionsResolver $resolver
     * @throws AccessException
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
     * Use label from formOptions or use translation or automatically create human readable one
     *
     * @param FamilyInterface $family
     * @param AttributeInterface $attribute
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getFieldLabel(FamilyInterface $family, AttributeInterface $attribute)
    {
        $tId = "eav.{$family->getCode()}.attribute.{$attribute->getCode()}.label";
        return $this->tryTranslate($tId, [], ucfirst($attribute));
    }
}
