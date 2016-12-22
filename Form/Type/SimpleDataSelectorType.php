<?php

namespace Sidus\EAVModelBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    /** @var string */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->fixDoctrineQueryBuilderNormalizer($resolver);

        $resolver->setRequired([
            'family',
        ]);

        $queryBuilder = function (EntityRepository $repository, $options) {
            $qb = $repository->createQueryBuilder('d');
            if (!empty($options['family'])) {
                $qb->addSelect('v')
                    ->leftJoin('d.values', 'v')
                    ->andWhere('d.family = :family')
                    ->setParameter('family', $options['family']);
            }
            $qb->setMaxResults(100);

            return $qb;
        };

        $resolver->setDefaults([
            'class' => $this->dataClass,
            'query_builder' => $queryBuilder,
        ]);
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
