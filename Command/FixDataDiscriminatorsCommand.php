<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * See command description
 *
 * WARNING, this commands uses raw SQL queries to fix Doctrine's discriminator column based on the family code, never
 * use this in production if you have changed the base relational model configuration like the column names
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FixDataDiscriminatorsCommand extends Command
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param FamilyRegistry  $familyRegistry
     * @param ManagerRegistry $doctrine
     */
    public function __construct(FamilyRegistry $familyRegistry, ManagerRegistry $doctrine)
    {
        parent::__construct();
        $this->familyRegistry = $familyRegistry;
        $this->doctrine = $doctrine;
    }


    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $description = 'Updates the database ensuring each data in the database has the proper Doctrine disciminator';
        $description .= 'corresponding to the data_class of the family in the model';
        $this
            ->setName('sidus:data:fix-discriminator')
            ->setDescription($description);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->familyRegistry->getFamilies() as $family) {
            $this->updateFamilyData($family, $output);
        }
    }

    /**
     * @param FamilyInterface $family
     * @param OutputInterface $output
     *
     * @throws \UnexpectedValueException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function updateFamilyData(FamilyInterface $family, OutputInterface $output)
    {
        if (!$family->isInstantiable()) {
            return;
        }
        $entityManager = $this->doctrine->getManagerForClass($family->getDataClass());
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \UnexpectedValueException("No manager found for class {$family->getDataClass()}");
        }
        $metadata = $entityManager->getClassMetadata($family->getDataClass());
        if (!$metadata->discriminatorColumn) {
            return;
        }
        $sql = $this->generateSql(
            $metadata->getTableName(),
            $metadata->discriminatorColumn['fieldName'],
            $metadata->getColumnName('family')
        );

        $count = $this->updateTable($entityManager, $sql, $metadata->discriminatorValue, $family->getCode());
        if ($count) {
            $output->writeln("<comment>{$count} data updated for family {$family->getCode()}</comment>");
        } else {
            $output->writeln("<info>No data to clean for family {$family->getCode()}</info>");
        }
    }

    /**
     * @param string $table
     * @param string $discriminatorColumn
     * @param string $familyColumn
     *
     * @return string
     */
    protected function generateSql($table, $discriminatorColumn, $familyColumn)
    {
        $sql = <<<EOS
UPDATE `{$table}`
    SET `{$discriminatorColumn}` = :discrValue
    WHERE
        `{$familyColumn}` = :familyCode AND `{$discriminatorColumn}` != :discrValue
EOS;

        return $sql;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $sql
     * @param string                 $discriminatorValue
     * @param string                 $familyCode
     *
     * @return int
     */
    protected function updateTable(EntityManagerInterface $entityManager, $sql, $discriminatorValue, $familyCode)
    {
        $connection = $entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':discrValue', $discriminatorValue);
        $stmt->bindValue(':familyCode', $familyCode);

        if (!$stmt->execute()) {
            throw new \RuntimeException("Unable to run SQL statement {$sql}");
        }

        return $stmt->rowCount();
    }
}
