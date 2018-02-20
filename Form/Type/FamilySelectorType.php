<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Form\Type;

use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Simple family selector
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilySelectorType extends AbstractType
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /**
     * @param FamilyRegistry $familyRegistry
     */
    public function __construct(FamilyRegistry $familyRegistry)
    {
        $this->familyRegistry = $familyRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws MissingFamilyException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($originalData) use ($options) {
                    if ($options['multiple'] && \is_array($originalData)) {
                        foreach ($originalData as $key => $originalDatum) {
                            if ($originalDatum instanceof FamilyInterface) {
                                $originalData[$key] = $originalDatum->getCode();
                            }
                        }

                        return $originalData;
                    }
                    if ($originalData instanceof FamilyInterface) {
                        $originalData = $originalData->getCode();
                    }

                    return $originalData;
                },
                function ($submittedData) use ($options) {
                    if ($submittedData === null) {
                        return $submittedData;
                    }
                    if ($submittedData instanceof FamilyInterface) {
                        // Should actually never happen ?
                        return $submittedData;
                    }

                    if ($options['multiple'] && \is_array($submittedData)) {
                        foreach ($submittedData as $key => $submittedDatum) {
                            if ($submittedDatum instanceof FamilyInterface) {
                                continue;
                            }
                            $submittedData[$key] = $this->familyRegistry->getFamily($submittedDatum);
                        }

                        return $submittedData;
                    }

                    return $this->familyRegistry->getFamily($submittedData);
                }
            )
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => null,
                'families' => null,
            ]
        );

        $resolver->setNormalizer(
            'families',
            function (Options $options, $values) {
                if (null === $values) {
                    $values = $this->familyRegistry->getFamilies();
                }
                $families = [];
                foreach ($values as $value) {
                    if (!$value instanceof FamilyInterface) {
                        $value = $this->familyRegistry->getFamily($value);
                    }
                    if ($value->isInstantiable()) {
                        $families[$value->getCode()] = $value;
                    }
                }

                return $families;
            }
        );
        $resolver->setNormalizer(
            'choices',
            function (Options $options, $value) {
                if (null !== $value) {
                    throw new \UnexpectedValueException(
                        "'choices' options is not supported for family selector, please use 'families' option"
                    );
                }
                $choices = [];
                /** @var FamilyInterface[] $families */
                $families = $options['families'];
                $duplicates = [];
                foreach ($families as $family) {
                    if ($family->isInstantiable()) {
                        $label = ucfirst($family);
                        if (array_key_exists($label, $choices)) {
                            // Storing duplicates
                            $duplicates[] = $label;
                            $label = "{$label} ({$family->getCode()})";
                        }
                        $choices[$label] = $family->getCode();
                    }
                }

                // Recreating duplicates
                foreach ($duplicates as $duplicate) {
                    $choices["{$duplicate} ({$choices[$duplicate]})"] = $choices[$duplicate];
                    unset($choices[$duplicate]);
                }
                ksort($choices);

                return $choices;
            }
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_family_selector';
    }
}
