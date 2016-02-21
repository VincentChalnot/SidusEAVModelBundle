<?php

namespace Sidus\EAVModelBundle\Form\Type;


use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ContextType extends AbstractType
{
    /** @var string */
    protected $contextClass;

    /**
     * @param string $contextClass
     */
    public function __construct($contextClass)
    {
        $this->contextClass = $contextClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country')
            ->add('language')
            ->add('channel')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->contextClass,
        ]);
    }

    public function getName()
    {
        return 'sidus_context';
    }
}
