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
