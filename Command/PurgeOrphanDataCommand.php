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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Sidus\EAVModelBundle\Entity\DataRepository;
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

        $qb = $em->createQueryBuilder()
            ->delete($this->dataClass, 'e')
            ->where('e.family NOT IN (:familyCodes)')
            ->setParameter('familyCodes', $familyCodes)
        ;
        $count = $qb->getQuery()->getResult();

        if ($count) {
            $output->writeln("<comment>{$count} data purged with missing family</comment>");
        } else {
            $output->writeln('<info>No data to purge</info>');
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
            ->setParameter('attributeCodes', $attributeCodes)
        ;
        $count = $qb->getQuery()->getResult();

        if ($count) {
            $output->writeln("<comment>{$count} values purged with missing attributes</comment>");
        } else {
            $output->writeln('<info>No values to purge</info>');
        }
    }
}
