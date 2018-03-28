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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * See command description
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class PurgeOrphanDataCommand extends ContainerAwareCommand
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var Registry */
    protected $doctrine;

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $valueClass;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $description = 'Purges all the data with a missing families and all the values with missing attributes';
        $this
            ->setName('sidus:data:purge-orphan-data')
            ->setDescription($description);
    }

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
        $this->familyRegistry = $this->getContainer()->get('sidus_eav_model.family.registry');
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->dataClass = $this->getContainer()->getParameter('sidus_eav_model.entity.data.class');
        $this->valueClass = $this->getContainer()->getParameter('sidus_eav_model.entity.value.class');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return int|null|void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->purgeMissingFamilies($output);
        $this->purgeMissingAttributes($output);
    }

    /**
     * @param OutputInterface $output
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function purgeMissingFamilies(OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $metadata = $em->getClassMetadata($this->dataClass);
        $table = $metadata->getTableName();
        $flattenedFamilyCodes = $this->quoteArray($em, $this->familyRegistry->getFamilyCodes());

        // LIMIT is not natively supported for delete statements in Doctrine
        $sql = "DELETE FROM `{$table}` WHERE family_code NOT IN ({$flattenedFamilyCodes}) LIMIT 1000";

        $count = $this->executeWithPaging($em, $sql);

        if ($count) {
            $output->writeln("<comment>{$count} data purged with missing family</comment>");
        } else {
            $output->writeln('<info>No data to purge</info>');
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function purgeMissingAttributes(OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $metadata = $em->getClassMetadata($this->valueClass);
        $table = $metadata->getTableName();

        foreach ($this->familyRegistry->getFamilies() as $family) {
            $attributeCodes = [];
            foreach ($family->getAttributes() as $attribute) {
                $attributeCodes[] = $attribute->getCode();
            }

            $quotedFamilyCode = $em->getConnection()->quote($family->getCode());
            $flattenedAttributeCodes = $this->quoteArray($em, $attributeCodes);

            // LIMIT is not natively supported for delete statements in Doctrine
            $sql = "DELETE FROM `{$table}` WHERE family_code = {$quotedFamilyCode} AND attribute_code NOT IN ({$flattenedAttributeCodes}) LIMIT 1000";

            $count = $this->executeWithPaging($em, $sql);

            if ($count) {
                $output->writeln("<comment>{$count} values purged in family {$family->getCode()} with missing attributes</comment>");
            } else {
                $output->writeln("<info>No values to purge for family {$family->getCode()}</info>");
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param string        $sql
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return int
     */
    protected function executeWithPaging(EntityManager $em, $sql)
    {
        $count = 0;
        do {
            $stmt = $em->getConnection()->executeQuery($sql);
            $stmt->execute();
            $lastCount = $stmt->rowCount();
            $count += $lastCount;
        } while ($lastCount > 0);

        return $count;
    }

    /**
     * Quote a PHP array to allow using it in native SQL query
     *
     * @param EntityManager $em
     * @param array         $array
     *
     * @return string
     */
    protected function quoteArray(EntityManager $em, array $array)
    {
        array_walk(
            $array,
            function (&$value) use ($em) {
                $value = $em->getConnection()->quote($value);
            }
        );

        return implode(', ', $array);
    }
}
