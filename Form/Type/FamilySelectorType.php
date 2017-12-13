<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
                foreach ($families as $family) {
                    if ($family->isInstantiable()) {
                        $choices[ucfirst($family)] = $family->getCode();
                    }
                }

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
