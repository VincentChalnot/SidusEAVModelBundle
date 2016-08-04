<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Sidus\EAVModelBundle\Doctrine\EAVQueryBuilder;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Model\IdentifierAttributeType;

/**
 * Base repository for Data, not currently used
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataRepository extends EntityRepository
{
    /**
     * @param FamilyInterface $family
     * @param int|string      $reference
     * @param bool            $idFallback
     * @return null|DataInterface
     * @throws NonUniqueResultException
     */
    public function findByIdentifier(FamilyInterface $family, $reference, $idFallback = false)
    {
        $identifierAttribute = $family->getAttributeAsIdentifier();
        if (!$identifierAttribute) {
            if (!$idFallback) {
                return null;
            }

            return $this->findOneBy([
                'id' => $reference,
                'family' => $family,
            ]);
        }
        $dataBaseType = $identifierAttribute->getType()->getDatabaseType();
        if ($identifierAttribute->getType() instanceof IdentifierAttributeType) {
            return $this->findOneBy([
                $dataBaseType => $reference,
                'family' => $family,
            ]);
        }
        $qb = $this->createQueryBuilder('d');
        $joinCondition = "(id.attributeCode = :attributeCode AND id.{$dataBaseType} = :reference)";
        $qb
            ->addSelect('values')
            ->join('d.values', 'id', Join::WITH, $joinCondition)
            ->join('d.values', 'values')
            ->where('d.family = :familyCode')
            ->setParameters([
                'attributeCode' => $identifierAttribute->getCode(),
                'reference' => $reference,
                'familyCode' => $family->getCode(),
            ])
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Return singleton for a given family
     *
     * @param FamilyInterface $family
     *
     * @throws \LogicException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return DataInterface
     */
    public function getInstance(FamilyInterface $family)
    {
        if (!$family->isSingleton()) {
            throw new \LogicException("Family {$family->getCode()} is not a singleton");
        }
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.family = :familyCode')
            ->addSelect('values')
            ->join('d.values', 'values')
            ->setParameters([
                'familyCode' => $family->getCode(),
            ])
        ;

        $instance = $qb->getQuery()->getOneOrNullResult();
        if (!$instance) {
            $dataClass = $family->getDataClass();
            $instance = new $dataClass($family);
        }

        return $instance;
    }

    /**
     * @param FamilyInterface $family
     * @param string          $alias
     * @return EAVQueryBuilder
     */
    public function createEAVQueryBuilder(FamilyInterface $family, $alias = 'e')
    {
        return new EAVQueryBuilder($family, $this->createQueryBuilder($alias), $alias);
    }
}
