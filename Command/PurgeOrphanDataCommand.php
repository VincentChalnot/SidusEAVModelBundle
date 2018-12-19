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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * See command description
 *
 * WARNING, this commands uses raw SQL queries to purge the orphan data from the database, never use this in production
 * if you have changed the base relational model configuration like the column names
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class PurgeOrphanDataCommand extends Command
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $valueClass;

    /**
     * @param FamilyRegistry  $familyRegistry
     * @param ManagerRegistry $doctrine
     * @param string          $dataClass
     * @param string          $valueClass
     */
    public function __construct(
        FamilyRegistry $familyRegistry,
        ManagerRegistry $doctrine,
        string $dataClass,
        string $valueClass
    ) {
        parent::__construct();
        $this->familyRegistry = $familyRegistry;
        $this->doctrine = $doctrine;
        $this->dataClass = $dataClass;
        $this->valueClass = $valueClass;
    }


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
     * @throws \Exception
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->doctrine->getManagerForClass($this->dataClass);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \UnexpectedValueException("No manager found for class {$this->dataClass}");
        }
        $this->purgeMissingFamilies($entityManager, $output);
        $this->purgeMissingAttributes($entityManager, $output);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param OutputInterface        $output
     */
    protected function purgeMissingFamilies(EntityManagerInterface $entityManager, OutputInterface $output)
    {
        $metadata = $entityManager->getClassMetadata($this->dataClass);
        $table = $metadata->getTableName();
        $flattenedFamilyCodes = $this->quoteArray($entityManager, $this->familyRegistry->getFamilyCodes());

        // LIMIT is not natively supported for delete statements in Doctrine
        $sql = "DELETE FROM `{$table}` WHERE family_code NOT IN ({$flattenedFamilyCodes}) LIMIT 1000";

        $count = $this->executeWithPaging($entityManager, $sql);

        if ($count) {
            $output->writeln("<comment>{$count} data purged with missing family</comment>");
        } else {
            $output->writeln('<info>No data to purge</info>');
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param OutputInterface        $output
     */
    protected function purgeMissingAttributes(EntityManagerInterface $entityManager, OutputInterface $output)
    {
        $metadata = $entityManager->getClassMetadata($this->valueClass);
        $table = $metadata->getTableName();

        foreach ($this->familyRegistry->getFamilies() as $family) {
            $attributeCodes = [];
            foreach ($family->getAttributes() as $attribute) {
                $attributeCodes[] = $attribute->getCode();
            }

            $quotedFamilyCode = $entityManager->getConnection()->quote($family->getCode());
            $flattenedAttributeCodes = $this->quoteArray($entityManager, $attributeCodes);

            // LIMIT is not natively supported for delete statements in Doctrine
            $sql = "DELETE FROM `{$table}` WHERE family_code = {$quotedFamilyCode} AND attribute_code NOT IN ({$flattenedAttributeCodes}) LIMIT 1000";

            $count = $this->executeWithPaging($entityManager, $sql);

            if ($count) {
                $output->writeln(
                    "<comment>{$count} values purged in family {$family->getCode()} with missing attributes</comment>"
                );
            } else {
                $output->writeln("<info>No values to purge for family {$family->getCode()}</info>");
            }
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $sql
     *
     * @return int
     */
    protected function executeWithPaging(EntityManagerInterface $entityManager, $sql)
    {
        $count = 0;
        do {
            /** @var Statement $stmt */
            $stmt = $entityManager->getConnection()->executeQuery($sql);
            $stmt->execute();
            $lastCount = $stmt->rowCount();
            $count += $lastCount;
        } while ($lastCount > 0);

        return $count;
    }

    /**
     * Quote a PHP array to allow using it in native SQL query
     *
     * @param EntityManagerInterface $entityManager
     * @param array                  $array
     *
     * @return string
     */
    protected function quoteArray(EntityManagerInterface $entityManager, array $array)
    {
        array_walk(
            $array,
            function (&$value) use ($entityManager) {
                $value = $entityManager->getConnection()->quote($value);
            }
        );

        return implode(', ', $array);
    }
}
