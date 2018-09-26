<?php

namespace Sidus\EAVModelBundle\Form;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Define shared logic for attribute / allowed_families option resolving
 */
class AllowedFamiliesOptionsConfigurator
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'attribute' => null,
                'allowed_families' => null,
            ]
        );

        $resolver->setAllowedTypes('attribute', ['NULL', AttributeInterface::class]);
        $resolver->setAllowedTypes('allowed_families', ['NULL', 'array']);
        $resolver->setNormalizer(
            'allowed_families',
            function (Options $options, $values) {
                if (null === $values) {
                    /** @var AttributeInterface $attribute */
                    $attribute = $options['attribute'];
                    if ($attribute) {
                        /** @var array $values */
                        $values = $attribute->getOption('allowed_families');
                    }
                    if (!$values) {
                        $values = $this->familyRegistry->getFamilies();
                    }
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
    }
}
