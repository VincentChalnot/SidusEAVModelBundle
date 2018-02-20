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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Simple data form selector, limits the options to 100
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SimpleDataSelectorType extends AbstractType
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var string */
    protected $dataClass;

    /**
     * @param FamilyRegistry $familyRegistry
     * @param string         $dataClass
     */
    public function __construct(FamilyRegistry $familyRegistry, $dataClass)
    {
        $this->familyRegistry = $familyRegistry;
        $this->dataClass = $dataClass;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->fixDoctrineQueryBuilderNormalizer($resolver);

        $queryBuilder = function (EntityRepository $repository, $options) {
            $qb = $repository->createQueryBuilder('d');
            if (!empty($options['allowed_families'])) {
                /** @var FamilyInterface[] $families */
                $families = $options['allowed_families'];
                $familyCodes = [];
                foreach ($families as $family) {
                    $familyCodes[] = $family->getCode();
                }

                $qb
                    ->andWhere('d.family IN (:allowedFamilies)')
                    ->setParameter('allowedFamilies', $familyCodes);
            }
            $qb->setMaxResults($options['max_results']);

            return $qb;
        };

        $resolver->setDefaults(
            [
                'class' => $this->dataClass,
                'query_builder' => $queryBuilder,
                'max_results' => 100,
                'attribute' => null,
                'allowed_families' => null,
            ]
        );

        $resolver->setAllowedTypes('attribute', [AttributeInterface::class, 'NULL']);
        $resolver->setAllowedTypes('allowed_families', ['array', 'NULL']);
        /** @noinspection PhpUnusedParameterInspection */
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

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_simple_data_selector';
    }

    /**
     * Adding an options parameter in the query builder normalizer
     * Taken directly from \Symfony\Bridge\Doctrine\Form\Type\EntityType
     *
     * @param OptionsResolver $resolver
     *
     * @throws \Exception
     */
    protected function fixDoctrineQueryBuilderNormalizer(OptionsResolver $resolver)
    {
        $queryBuilderNormalizer = function (Options $options, $queryBuilder) {
            if (is_callable($queryBuilder)) {
                $queryBuilder = call_user_func(
                    $queryBuilder,
                    $options['em']->getRepository($options['class']),
                    $options
                );

                if (!$queryBuilder instanceof QueryBuilder) {
                    throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder');
                }
            }

            return $queryBuilder;
        };

        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);
    }
}
