<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Doctrine\EAVQueryBuilder;
use Sidus\EAVModelBundle\Doctrine\SingleFamilyQueryBuilder;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Model\IdentifierAttributeType;

/**
 * Base repository for Data
 *
 * The $partialLoad option triggers the Query Hint HINT_FORCE_PARTIAL_LOAD
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataRepository extends EntityRepository
{
    /**
     * Find data based on it's family identifier
     *
     * @param FamilyInterface $family
     * @param int|string      $reference
     * @param bool            $idFallback
     * @param bool            $partialLoad
     *
     * @throws NonUniqueResultException
     * @throws \UnexpectedValueException
     * @throws ORMException
     * @throws NoResultException
     * @throws MappingException
     *
     * @return null|DataInterface
     * @throws \LogicException
     */
    public function findByIdentifier(FamilyInterface $family, $reference, $idFallback = false, $partialLoad = false)
    {
        $identifierAttribute = $family->getAttributeAsIdentifier();
        if (!$identifierAttribute) {
            if (!$idFallback) {
                $m = "Cannot find data with no identifier attribute for family: '{$family->getCode()}'";
                throw new \UnexpectedValueException($m);
            }

            return $this->findByPrimaryKey($family, $reference, $partialLoad);
        }

        return $this->findByUniqueAttribute($family, $identifierAttribute, $reference, $partialLoad);
    }

    /**
     * Find a data based on a unique attribute
     *
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param string|int         $reference
     * @param bool               $partialLoad
     *
     * @return Proxy|mixed|null|DataInterface
     * @throws \LogicException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws NonUniqueResultException
     */
    public function findByUniqueAttribute(
        FamilyInterface $family,
        AttributeInterface $attribute,
        $reference,
        $partialLoad = false
    ) {
        if (!$attribute->isUnique()) {
            throw new \LogicException("Cannot find data based on a non-unique attribute '{$attribute->getCode()}'");
        }
        $dataBaseType = $attribute->getType()->getDatabaseType();
        if ($attribute->getType() instanceof IdentifierAttributeType) {
            return $this->findByIdentifierColumn($family, $dataBaseType, $reference, $partialLoad);
        }
        $qb = $this->createQueryBuilder('e');
        $joinCondition = "(identifier.attributeCode = :attributeCode AND identifier.{$dataBaseType} = :reference)";
        $qb
            ->join('e.values', 'identifier', Join::WITH, $joinCondition)
            ->where('e.family = :familyCode')
            ->setParameters(
                [
                    'attributeCode' => $attribute->getCode(),
                    'reference' => $reference,
                    'familyCode' => $family->getCode(),
                ]
            );

        if ($partialLoad) {
            return $this->executeWithPartialLoad($qb);
        }

        // Optimize values querying, @T0D0 check if really a good idea ?
        $qb
            ->addSelect('values')
            ->join('e.values', 'values');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find by the Data entity primary key like a find()
     *
     * @param FamilyInterface $family
     * @param string|int      $reference
     * @param bool            $partialLoad
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return Proxy|null|DataInterface
     */
    public function findByPrimaryKey(
        FamilyInterface $family,
        $reference,
        $partialLoad = false
    ) {
        $identifierColumn = $this->getPkColumn($family);

        return $this->findByIdentifierColumn($family, $identifierColumn, $reference, $partialLoad);
    }

    /**
     * Find data based on a identifier column present in the Data entity
     *
     * @param FamilyInterface $family
     * @param string          $identifierColumn
     * @param int|string      $reference
     * @param bool            $partialLoad
     *
     * @throws ORMException
     *
     * @return DataInterface|Proxy|null
     */
    public function findByIdentifierColumn(
        FamilyInterface $family,
        $identifierColumn,
        $reference,
        $partialLoad = false
    ) {
        if (!$partialLoad) {
            return $this->findOneBy(
                [
                    $identifierColumn => $reference,
                    'family' => $family,
                ]
            );
        }

        $qb = $this->createQueryBuilder('e')
            ->where("e.{$identifierColumn} = :reference")
            ->andWhere('e.family = :familyCode')
            ->setParameters(
                [
                    'reference' => $reference,
                    'familyCode' => $family->getCode(),
                ]
            );

        return $this->executeWithPartialLoad($qb);
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
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.family = :familyCode')
            ->addSelect('values')
            ->join('e.values', 'values')
            ->setParameters(
                [
                    'familyCode' => $family->getCode(),
                ]
            );

        $instance = $qb->getQuery()->getOneOrNullResult();
        if (!$instance) {
            $dataClass = $family->getDataClass();
            $instance = new $dataClass($family);
        }

        return $instance;
    }

    /**
     * Returns a EAVQueryBuilder to allow you to build a complex query to search your database
     *
     * @param FamilyInterface $family
     * @param string          $alias
     *
     * @return SingleFamilyQueryBuilder
     */
    public function createFamilyQueryBuilder(FamilyInterface $family, $alias = 'e')
    {
        return new SingleFamilyQueryBuilder($family, $this->createQueryBuilder($alias), $alias);
    }

    /**
     * @param string $alias
     *
     * @return EAVQueryBuilder
     */
    public function createEAVQueryBuilder($alias = 'e')
    {
        return new EAVQueryBuilder($this->createQueryBuilder($alias), $alias);
    }

    /**
     * @param FamilyInterface[] $families
     * @param string            $term
     *
     * @throws \LogicException
     * @throws \UnexpectedValueException
     *
     * @return QueryBuilder
     */
    public function getQbForFamiliesAndLabel(array $families, $term)
    {
        $eavQb = $this->createEAVQueryBuilder();
        $orCondition = [];
        foreach ($families as $family) {
            $orCondition[] = $eavQb->attribute($family->getAttributeAsLabel())->like($term);
        }

        return $eavQb->apply($eavQb->getOr($orCondition));
    }

    /**
     * @param FamilyInterface $family
     *
     * @throws MappingException
     *
     * @return string
     */
    protected function getPkColumn(FamilyInterface $family)
    {
        return $this->getEntityManager()
            ->getClassMetadata($family->getDataClass())
            ->getSingleIdentifierFieldName();
    }

    /**
     * @param QueryBuilder $qb
     *
     * @throws NonUniqueResultException
     *
     * @return mixed
     */
    protected function executeWithPartialLoad(QueryBuilder $qb)
    {
        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $query->getOneOrNullResult();
    }
}
