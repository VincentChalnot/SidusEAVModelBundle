<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Sidus\EAVModelBundle\Model\FamilyInterface;

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
     * @return DataInterface|null
     * @throws NonUniqueResultException
     */
    public function findByIdentifier(FamilyInterface $family, $reference)
    {
        $identifierAttribute = $family->getAttributeAsIdentifier();
        if (!$identifierAttribute) {
            return $this->find($reference);
        }
        $qb = $this->createQueryBuilder('d');
        $dataBaseType = $identifierAttribute->getType()->getDatabaseType();
        $joinCondition = "(id.attributeCode = :attributeCode AND id.{$dataBaseType} = :reference)";
        $qb
            ->addSelect('values')
            ->join('d.values', 'id', Join::WITH, $joinCondition)
            ->join('d.values', 'values')
            ->setParameters([
                'attributeCode' => $identifierAttribute->getCode(),
                'reference' => $reference,
            ])
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}
