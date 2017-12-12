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

use Sidus\EAVModelBundle\Form\AttributeFormBuilderInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
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

    /**
     * @param AttributeFormBuilderInterface $attributeFormBuilder
     * @param FamilyRegistry                $familyRegistry
     */
    public function __construct(
        AttributeFormBuilderInterface $attributeFormBuilder,
        FamilyRegistry $familyRegistry
    ) {
        $this->attributeFormBuilder = $attributeFormBuilder;
        $this->familyRegistry = $familyRegistry;
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

        $attributes = $family->getAttributes();
        if ($options['attributes_config']) {
            $attributes = [];
            foreach (array_keys($options['attributes_config']) as $attributeCode) {
                $attributes[] = $family->getAttribute($attributeCode);
            }
        }

        foreach ($attributes as $attribute) {
            $this->attributeFormBuilder->addAttribute(
                $builder,
                $attribute,
                $this->resolveAttributeConfig($attribute, $options)
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     * @throws MissingFamilyException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'empty_data' => null,
                'data_class' => null,
                'attributes_config' => null,
                'attribute' => null,
                'family' => null,
            ]
        );
        $resolver->setAllowedTypes('attributes_config', ['NULL', 'array']);
        $resolver->setAllowedTypes('attribute', ['NULL', AttributeInterface::class]);
        $resolver->setAllowedTypes('family', ['NULL', 'string', FamilyInterface::class]);

        $resolver->setNormalizer(
            'family',
            function (Options $options, $value) {
                // If family option is not set, try to fetch the family from the attribute option
                if ($value === null) {
                    /** @var AttributeInterface $attribute */
                    $attribute = $options['attribute'];
                    if (!$attribute) {
                        throw new MissingOptionsException(
                            "An option is missing: you must set either the 'family' option or the 'attribute' option"
                        );
                    }
                    $allowedFamilies = $attribute->getOption('allowed_families', []);
                    if (1 !== \count($allowedFamilies)) {
                        $m = "Can't automatically compute the 'family' option with an attribute with no family allowed";
                        $m .= " or multiple allowed families, please set the 'family' option manually";
                        throw new MissingOptionsException($m);
                    }

                    $value = reset($allowedFamilies);
                }

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
                /** @var FamilyInterface $family */
                $family = $options['family'];

                return $family->createData();
            }
        );
        $resolver->setNormalizer(
            'data_class',
            function (Options $options, $value) {
                if (null !== $value) {
                    $m = "DataType form does not supports the 'data_class' option, it will be automatically resolved";
                    $m .= ' with the family';
                    throw new \UnexpectedValueException($m);
                }
                /** @var FamilyInterface $family */
                $family = $options['family'];

                return $family->getDataClass();
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

    /**
     * @param AttributeInterface $attribute
     * @param array              $options
     *
     * @return array
     */
    protected function resolveAttributeConfig(AttributeInterface $attribute, array $options)
    {
        if (isset($options['attributes_config'][$attribute->getCode()])) {
            return $options['attributes_config'][$attribute->getCode()];
        }
        $attributeConfig = $attribute->getOption('attribute_config');
        if (null !== $attributeConfig) {
            return $attributeConfig;
        }

        return [];
    }
}
