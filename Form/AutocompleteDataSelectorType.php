<?php

namespace Sidus\EAVModelBundle\Form;

use Doctrine\ORM\EntityRepository;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutocompleteDataSelectorType extends AbstractType
{
    /** @var string */
    protected $dataClass;

    /** @var EntityRepository */
    protected $repository;

    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /**
     * @param $dataClass
     * @param EntityRepository $repository
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     */
    public function __construct($dataClass, EntityRepository $repository, FamilyConfigurationHandler $familyConfigurationHandler)
    {
        $this->dataClass = $dataClass;
        $this->repository = $repository;
        $this->familyConfigurationHandler = $familyConfigurationHandler;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (empty($view->vars['attr']['class'])) {
            $view->vars['attr']['class'] = 'select2';
        } else {
            $view->vars['attr']['class'] .= ' select2';
        }
        $view->vars['attr']['data-placeholder'] = $options['placeholder'];
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FamilyInterface $family */
        $family = $options['family_code'];
        $qb = $this->repository->createQueryBuilder('d');
        $qb->innerJoin('d.values', 'v')
            ->andWhere('d.familyCode = :familyCode')
            ->andwhere('v.attributeCode = :attributeCode')
            ->setParameter('attributeCode', $family->getAttributeAsLabel()->getCode())
            ->setParameter('familyCode', $family->getCode());
        $builder->setAttribute('query-builder', $qb);
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'family_code',
        ]);

        $resolver->setDefaults([
            'class' => $this->dataClass,
            'search_fields' => ['v.stringValue'],
            'template' => 'SidusEAVModelBundle:Data:data_autocomplete.html.twig',
        ]);
        $familyConfigurationHandler = $this->familyConfigurationHandler;
        $resolver->setNormalizer('family_code', function (Options $options, $value) use ($familyConfigurationHandler) {
            $family = $familyConfigurationHandler->getFamily($value);
            if (!$family) {
                throw new \UnexpectedValueException("Unknown family option {$family}");
            }
            return $family;
        });
    }

    public function getParent()
    {
        return 'autocomplete';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_autocomplete_data_selector';
    }
}
