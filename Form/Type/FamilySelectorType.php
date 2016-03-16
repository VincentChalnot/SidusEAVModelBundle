<?php

namespace Sidus\EAVModelBundle\Form\Type;


use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Options;
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

    /**
     * @inheritDoc
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($originalData) use ($options) {
                if ($originalData instanceof FamilyInterface) {
                    $originalData = $originalData->getCode();
                }
                return $originalData;
            },
            function ($submittedData) {
                if ($submittedData instanceof FamilyInterface) {
                    // Should actually never happen ?
                    return $submittedData;
                }
                try {
                    return $this->familyConfigurationHandler->getFamily($submittedData);
                } catch (MissingFamilyException $e) {
                    throw new \InvalidArgumentException($e->getMessage(), 0, $e);
                }
            }
        ));
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices_as_values' => true,
            'choices' => null,
            'families' => null,
        ]);

        $resolver->setNormalizer('families', function (Options $options, $values) {
            if (null === $values) {
                $values = $this->familyConfigurationHandler->getFamilies();
            }
            $families = [];
            foreach ($values as $value) {
                if (!$value instanceof FamilyInterface) {
                    $value = $this->familyConfigurationHandler->getFamily($value);
                }
                if ($value->isInstantiable()) {
                    $families[$value->getCode()] = $value;
                }
            }
            return $families;
        });
        $resolver->setNormalizer('choices_as_values', function (Options $options, $value) {
            if ($value !== true) {
                throw new \UnexpectedValueException("'choices_as_values' must be true (and is by default)");
            }
            return true;
        });
        $resolver->setNormalizer('choices', function (Options $options, $value) {
            if (null !== $value) {
                throw new \UnexpectedValueException("'choices' options is not supported for family selector, please use 'families' option");
            }
            $choices = [];
            /** @var FamilyInterface $family */
            foreach ($options['families'] as $family) {
                if ($family->isInstantiable()) {
                    $choices[ucfirst($family)] = $family->getCode();
                }
            }
            return $choices;
        });
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