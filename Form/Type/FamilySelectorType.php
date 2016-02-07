<?php

namespace Sidus\EAVModelBundle\Form\Type;


use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class FamilySelectorType extends AbstractType
{
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /**
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     */
    public function __construct(FamilyConfigurationHandler $familyConfigurationHandler)
    {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach ($this->familyConfigurationHandler->getFamilies() as $family) {
            if ($family->isInstantiable()) {
                $choices[ucfirst($family)] = $family;
            }
        }

        $resolver->setDefaults([
            'choices_as_values' => true,
            'choices' => $choices,
        ]);
    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'sidus_family_selector';
    }
}