<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Doctrine\EAVFinder;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanDataCommand
 *
 * Clean log table
 *
 * Exemple :
 * - `--family-filter="Order"` : remove data of Order family only
 * - `--family-filter="Order" --attribute-filter="number:like:000%" --attribute-filter="createdAt:>:+ 1 year"` : remove all orders with number starts with '000' and older than 1 year
 * - `--family-filter='OrderItem' --attribute-filters='id:not in:Order.items'` : remove all order items not related with orders
 *
 * @package CleverAge\ProcessBundle\Command
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class CleanDataCommand extends ContainerAwareCommand
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var EAVFinder */
    protected $eavFinder;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $valueClass;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \LogicException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->eavFinder = $this->getContainer()->get('sidus_eav_model.finder');
        $this->familyRegistry = $this->getContainer()->get('sidus_eav_model.family.registry');
        $this->dataClass = $this->getContainer()->getParameter('sidus_eav_model.entity.data.class');
        $this->valueClass = $this->getContainer()->getParameter('sidus_eav_model.entity.value.class');
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('sidus:data:clean-data');
        $this->addOption(
            'family-filter',
            null,
            InputOption::VALUE_REQUIRED,
            'Code of the family'
        );
        $this->addOption(
            'attribute-filters',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Filter on attribute with format %attribute%:%comparaison-operator%:%value%'
        );
        $this->addOption(
            'remove-orphan-data',
            null,
            InputOption::VALUE_NONE,
            'Clean orphan data items'
        );
        $this->addOption(
            'remove-missing-families',
            null,
            InputOption::VALUE_NONE,
            'Clean data items with a missing family'
        );
        $this->addOption(
            'remove-missing-attributes',
            null,
            InputOption::VALUE_NONE,
            'Clean value items with a missing attributes'
        );
    }

    /**
     * {@inheritdoc}
     * @throws \UnexpectedValueException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     * @throws \RuntimeException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('fire', new OutputFormatterStyle('red'));

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();

        try {
            $familyFilter = $this->extractFamilyFilterOption(
                $input->getOption('family-filter')
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Failed to parse value filters : '.$e->getMessage());
        }
        try {
            $attributeFilters = $this->extractAttributeFilterOptions(
                $familyFilter,
                $input->getOption('attribute-filters')
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Failed to parse attribute filters : '.$e->getMessage());
        }

        if ($familyFilter) {
            $result = $this
                ->cleanDataWithAttributeFilter(
                    $familyFilter,
                    $attributeFilters
                );
            $output->writeln(sprintf('Data : %d filtred item(s) removed', $result));
        }

        if ($input->getOption('remove-orphan-data')) {
            $result = $this
                ->cleanOrphanData();
            $output->writeln(sprintf('Data : %d orphan item(s) removed', $result));
        }

        if ($input->getOption('remove-missing-families')) {
            $result = $this
                ->purgeMissingFamilies();
            $output->writeln(sprintf('Family : %d missing item(s) removed', $result));
        }

        if ($input->getOption('remove-missing-attributes')) {
            $result = $this
                ->purgeMissingAttributes();
            $output->writeln(sprintf('Attribute : %d missing item(s) removed', $result));
        }
    }

    /**
     * @param string $input
     * @return FamilyInterface
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \InvalidArgumentException
     */
    protected function extractFamilyFilterOption(string $input)
    {
        if (!$input) {
            return null;
        }

        if ($this->familyRegistry->hasFamily($input)) {
            return $this->familyRegistry->getFamily($input);
        }

        throw new \InvalidArgumentException('Family not found');
    }

    /**
     * @param FamilyInterface $family
     * @param array           $input
     * @return array
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     * @throws \InvalidArgumentException
     */
    protected function extractAttributeFilterOptions(FamilyInterface $family, array $input)
    {
        if (!$family) {
            return [];
        }
        $attributeCodes = ['id'];
        foreach ($family->getAttributes() as $attribute) {
            $attributeCodes[] = $attribute->getCode();
        }

        array_walk(
            $input,
            function (&$item) use ($family, $attributeCodes) {
                $item = $this->parseFilter($item, $attributeCodes);
                $item['family'] = $family;
                $item['attribute'] = 'id' === $item['property'] ? null : $family->getAttribute($item['property']);
                $this->fixFilter($item);
            }
        );

        return $input;
    }

    /**
     * @param string $input
     * @param array  $validProperties
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function parseFilter(string $input, array $validProperties)
    {
        $pattern = sprintf(
            '/(%s):(%s):(.+)/',
            implode('|', $validProperties),
            implode('|', EAVFinder::FILTER_OPERATORS)
        );
        $match = preg_match($pattern, $input, $parts);

        if (1 === $match) {
            return array_combine(['filter', 'property', 'comparison', 'value'], $parts);
        }

        throw new \InvalidArgumentException(sprintf('Invalid filter %s', $input));
    }

    /**
     * @param array $filter
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     */
    protected function fixFilter(array &$filter)
    {
        if (in_array($filter['comparison'], ['in', 'not in'], true)) {
            $families = $this->familyRegistry->getFamilyCodes();
            $pattern = sprintf(
                '/(%s).([\w]+)/',
                implode('|', $families)
            );
            $match = preg_match($pattern, $filter['value'], $parts);

            if (1 === $match) {
                $targetFamily = $this->familyRegistry->getFamily($parts[1]);
                $targetAttribute = $targetFamily->getAttribute($parts[2]);
                $queryBuilderAlias = uniqid('subQuery', false);
                $familyCodeParam = 'familyCode'.$queryBuilderAlias;
                $attributeCodeParam = 'attributeCode'.$queryBuilderAlias;

                $queryBuilder = $this
                    ->entityManager
                    ->getRepository($this->valueClass)
                    ->createQueryBuilder($queryBuilderAlias)
                    ->select(
                        sprintf('IDENTITY(%s.%s)', $queryBuilderAlias, $targetAttribute->getType()->getDatabaseType())
                    )
                    ->distinct()
                    ->where(sprintf('%s.familyCode = :%s', $queryBuilderAlias, $familyCodeParam))
                    ->setParameter($familyCodeParam, $targetFamily->getCode())
                    ->andWhere(sprintf('%s.attributeCode = :%s', $queryBuilderAlias, $attributeCodeParam))
                    ->setParameter($attributeCodeParam, $targetAttribute->getCode());

                $filter['value'] = clone $queryBuilder;
            } else {
                $filter['value'] = explode(',', $filter['value']);
            }
        }

        if ($filter['attribute']
            && in_array($filter['attribute']->getType()->getCode(), ['date', 'datetime'], true)) {
            $filter['value'] = new \DateTime($filter['value']);
        }
    }

    /**
     * Remove value history items based en data and value filters
     *
     * @param FamilyInterface $family
     * @param array[]         $filters
     * @return int
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     */
    protected function cleanDataWithAttributeFilter(FamilyInterface $family, array $filters)
    {
        if (!$family) {
            throw new \InvalidArgumentException('No family found');
        }

        $idFiltersKeys = array_keys(array_column($filters, 'property'), 'id');
        $idFilters = [];
        foreach ($idFiltersKeys as $filtersKey) {
            $idFilters[] = $filters[$filtersKey];
            unset($filters[$filtersKey]);
        }

        array_walk(
            $filters,
            function (&$item) {
                $item = [
                    $item['property'],
                    $item['comparison'],
                    $item['value'],
                ];
            }
        );
        $queryBuilder = $this->eavFinder->getFilterByQb($family, $filters);
        $queryBuilderAlias = current($queryBuilder->getRootAliases());

        /** @noinspection ForeachSourceInspection */
        foreach ($idFilters as $filter) {
            $comparison = $filter['comparison'];
            $value = $filter['value'];

            if ($value instanceof QueryBuilder) {
                foreach ($value->getParameters() as $parameter) {
                    $queryBuilder->setParameter($parameter->getName(), $parameter->getValue());
                }
                $value = $value->getQuery()->getDQL();
            }
            if ('=' === $comparison) {
                $queryPart = $queryBuilder->expr()->eq($queryBuilderAlias, $value);
            } elseif ('!=' === $comparison) {
                $queryPart = $queryBuilder->expr()->neq($queryBuilderAlias, $value);
            } elseif ('>' === $comparison) {
                $queryPart = $queryBuilder->expr()->gt($queryBuilderAlias, $value);
            } elseif ('<' === $comparison) {
                $queryPart = $queryBuilder->expr()->lt($queryBuilderAlias, $value);
            } elseif ('>=' === $comparison) {
                $queryPart = $queryBuilder->expr()->gte($queryBuilderAlias, $value);
            } elseif ('<=' === $comparison) {
                $queryPart = $queryBuilder->expr()->lte($queryBuilderAlias, $value);
            } elseif ('in' === $comparison) {
                $queryPart = $queryBuilder->expr()->in($queryBuilderAlias, $value);
            } elseif ('not in' === $comparison) {
                $queryPart = $queryBuilder->expr()->notIn($queryBuilderAlias, $value);
            } elseif ('like' === $comparison) {
                $queryPart = $queryBuilder->expr()->like($queryBuilderAlias, $value);
            } elseif ('not like' === $comparison) {
                $queryPart = $queryBuilder->expr()->notLike($queryBuilderAlias, $value);
            } else {
                throw new \InvalidArgumentException('Invalid comparison');
            }

            $queryBuilder->andWhere($queryPart);
        }

        $queryBuilder
            ->distinct()
            ->setMaxResults(1);
        $i = 0;
        while ($item = $queryBuilder->getQuery()->getOneOrNullResult()) {
            $this
                ->entityManager
                ->remove($item);
            $this
                ->entityManager
                ->flush();
            if (($i % 100) === 0) {
                $this
                    ->entityManager
                    ->clear();
            }
            ++$i;
        }
        $this
            ->entityManager
            ->clear();

        return $i;
    }

    /**
     * Remove data history items without value history
     *
     * @return int
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    protected function cleanOrphanData()
    {
        $valueSubQueryBuilder = $this
            ->entityManager
            ->getRepository($this->valueClass)
            ->createQueryBuilder('value')
            ->select('IDENTITY(value.data)');

        $dataQueryBuilder = $this
            ->entityManager
            ->getRepository($this->dataClass)
            ->createQueryBuilder('data');
        $dataQueryBuilder
            ->delete()
            ->where($dataQueryBuilder->expr()->notIn('data', $valueSubQueryBuilder->getDQL()));

        return $dataQueryBuilder->getQuery()->execute();

    }

    /**
     * @return int
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function purgeMissingFamilies()
    {
        $familyCodes = $this->familyRegistry->getFamilyCodes();

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->delete($this->dataClass, 'e')
            ->where('e.family NOT IN (:familyCodes)')
            ->setParameter('familyCodes', $familyCodes);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return int
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function purgeMissingAttributes()
    {
        $attributeCodes = [];
        foreach ($this->familyRegistry->getFamilies() as $family) {
            foreach ($family->getAttributes() as $attribute) {
                $attributeCodes[] = $family->getCode().'.'.$attribute->getCode();
            }
        }
        $attributeCodes = array_unique($attributeCodes);

        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->delete($this->valueClass, 'e')
            ->where("CONCAT(e.familyCode,'.',e.attributeCode) NOT IN (:attributeCodes)")
            ->setParameter('attributeCodes', $attributeCodes);

        return $queryBuilder->getQuery()->getResult();
    }
}
