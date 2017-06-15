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

namespace Sidus\EAVModelBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * See command description
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FixDataDiscriminatorsCommand extends ContainerAwareCommand
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var Registry */
    protected $doctrine;

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
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \LogicException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->familyRegistry = $this->getContainer()->get('sidus_eav_model.family.registry');
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
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
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function updateFamilyData(FamilyInterface $family, OutputInterface $output)
    {
        if (!$family->isInstantiable()) {
            return;
        }
        $em = $this->doctrine->getManager();
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $em->getClassMetadata($family->getDataClass());
        if (!$metadata->discriminatorColumn) {
            return;
        }
        $sql = $this->generateSql(
            $metadata->getTableName(),
            $metadata->discriminatorColumn['fieldName'],
            $metadata->getColumnName('family')
        );

        $count = $this->updateTable($sql, $metadata->discriminatorValue, $family->getCode());
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
     * @param string $sql
     * @param string $discriminatorValue
     * @param string $familyCode
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     *
     * @return int
     */
    protected function updateTable($sql, $discriminatorValue, $familyCode)
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':discrValue', $discriminatorValue);
        $stmt->bindValue(':familyCode', $familyCode);

        if (!$stmt->execute()) {
            throw new \RuntimeException("Unable to run SQL statement {$sql}");
        }

        return $stmt->rowCount();
    }
}
