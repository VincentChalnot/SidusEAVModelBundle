<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Form\AllowedFamiliesOptionsConfigurator;
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
    /** @var AllowedFamiliesOptionsConfigurator */
    protected $allowedFamiliesOptionConfigurator;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var string */
    protected $dataClass;

    /**
     * @param AllowedFamiliesOptionsConfigurator $allowedFamiliesOptionConfigurator
     * @param ManagerRegistry                    $managerRegistry
     * @param string                             $dataClass
     */
    public function __construct(
        AllowedFamiliesOptionsConfigurator $allowedFamiliesOptionConfigurator,
        ManagerRegistry $managerRegistry,
        $dataClass
    ) {
        $this->allowedFamiliesOptionConfigurator = $allowedFamiliesOptionConfigurator;
        $this->doctrine = $managerRegistry;
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
                'em' => $this->doctrine->getManagerForClass($this->dataClass),
                'class' => $this->dataClass,
                'query_builder' => $queryBuilder,
                'max_results' => 100,
            ]
        );

        $this->allowedFamiliesOptionConfigurator->configureOptions($resolver);
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
            if (\is_callable($queryBuilder)) {
                $queryBuilder = $queryBuilder($options['em']->getRepository($options['class']), $options);

                if (!$queryBuilder instanceof QueryBuilder) {
                    throw new UnexpectedTypeException($queryBuilder, QueryBuilder::class);
                }
            }

            return $queryBuilder;
        };

        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);
    }
}
