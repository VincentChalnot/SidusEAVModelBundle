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
use Doctrine\ORM\EntityManager;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
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
        $description = 'Purges all the data with a missing family and all the values with missing attributes';
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
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->purgeMissingFamilies($output);
        $this->purgeMissingAttributes($output);
    }

    /**
     * The purge is processed 1000 by 1000 to avoid everlasting mysql process when the count is way too massive
     *
     * @param OutputInterface $output
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function purgeMissingFamilies(OutputInterface $output)
    {
        $familyCodes = $this->familyRegistry->getFamilyCodes();
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        // Raw SQL because Doctrine does not handle limit in delete
        $parameterSql = '';
        foreach ($familyCodes as $familyCode) {
            $parameterSql .= "'".$familyCode."',";
        }
        $parameterSql = trim($parameterSql, ',');

        // TODO is it necessary ?
        $valueSql = 'DELETE FROM eav_value WHERE family_code NOT IN ('.$parameterSql.') LIMIT 10000';
        $dataSql = 'DELETE FROM eav_data WHERE family_code NOT IN ('.$parameterSql.') LIMIT 10000';

        foreach (['value' => $valueSql, 'data' => $dataSql] as $type => $sql) {
            $output->writeln("<info>Cleaning $type</info>");
            $progress = new ProgressBar($output);
            // TODO add count

            do {
                $stmt = $em->getConnection()->prepare($valueSql);
                $success = $stmt->execute();
                $count = $stmt->rowCount();
                $progress->advance($count);
            } while ($success && $count > 0);

            $progress->finish();
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function purgeMissingAttributes(OutputInterface $output)
    {
        $attributeCodes = [];
        foreach ($this->familyRegistry->getFamilies() as $family) {
            foreach ($family->getAttributes() as $attribute) {
                $attributeCodes[] = $attribute->getCode();
            }
        }
        $attributeCodes = array_unique($attributeCodes);
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $qb = $em->createQueryBuilder()
            ->delete($this->valueClass, 'e')
            ->where('e.attributeCode NOT IN (:attributeCodes)')
            ->setParameter('attributeCodes', $attributeCodes);
        $count = $qb->getQuery()->getResult();

        if ($count) {
            $output->writeln("<comment>{$count} values purged with missing attributes</comment>");
        } else {
            $output->writeln('<info>No values to purge</info>');
        }
    }
}
