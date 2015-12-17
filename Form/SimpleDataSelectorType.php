<?php

namespace Sidus\EAVModelBundle\Form;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SimpleDataSelectorType extends AbstractType
{
    /** @var string */
    protected $dataClass;

    /**
     * @param $dataClass
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->fixDoctrineQueryBuilderNormalizer($resolver);

        $resolver->setRequired([
            'family_code',
        ]);

        $queryBuilder = function (EntityRepository $repository, $options) {
            $qb = $repository->createQueryBuilder('d');
            if (isset($options['family_code'])) {
                $qb->addSelect('v')
                    ->leftJoin('d.values', 'v')
                    ->andWhere('d.familyCode = :familyCode')
                    ->setParameter('familyCode', $options['family_code']);
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
     * Adding an options parameter in the query builder normalizer
     * Taken directly from \Symfony\Bridge\Doctrine\Form\Type\EntityType
     *
     * @param OptionsResolver $resolver
     */
    protected function fixDoctrineQueryBuilderNormalizer(OptionsResolver $resolver)
    {
        $queryBuilderNormalizer = function (Options $options, $queryBuilder) {
            if (is_callable($queryBuilder)) {
                $queryBuilder = call_user_func($queryBuilder, $options['em']->getRepository($options['class']), $options);

                if (!$queryBuilder instanceof QueryBuilder) {
                    throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder');
                }
            }

            return $queryBuilder;
        };

        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);
    }

    public function getParent()
    {
        return 'entity';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_simple_data_selector';
    }
}
