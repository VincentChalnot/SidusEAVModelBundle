<?php

namespace Sidus\EAVModelBundle\Doctrine;

use Sidus\EAVModelBundle\Entity\DataRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Use this service as a wrapper of the EAVQueryBuilder API to find data based on attributes values
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVFinder
{
    /** @var Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     *
     * @return array
     */
    public function findBy(FamilyInterface $family, array $filterBy, array $orderBy = [])
    {
        $qb = $this->getQb($family, $filterBy, $orderBy);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws NonUniqueResultException
     *
     * @return DataInterface
     */
    public function findOneBy(FamilyInterface $family, array $filterBy)
    {
        $qb = $this->getQb($family, $filterBy);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param FamilyInterface $family
     * @param array           $filterBy
     * @param array           $orderBy
     * @param string          $alias
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     *
     * @return QueryBuilder
     */
    public function getQb(FamilyInterface $family, array $filterBy, array $orderBy = [], $alias = 'e')
    {
        $eavQb = $this->getRepository($family)->createFamilyQueryBuilder($family, $alias);

        // Add order by
        foreach ($orderBy as $attributeCode => $direction) {
            $eavQb->addOrderBy($eavQb->a($attributeCode), $direction);
        }

        $dqlHandlers = [];
        foreach ($filterBy as $attributeCode => $value) {
            $attributeQb = $eavQb->a($attributeCode);
            if (is_array($value)) {
                $dqlHandlers[] = $attributeQb->in($value);
            } else {
                $dqlHandlers[] = $attributeQb->equals($value);
            }
        }

        return $eavQb->apply($eavQb->getAnd($dqlHandlers));
    }

    /**
     * @param FamilyInterface $family
     *
     * @return DataRepository
     */
    public function getRepository(FamilyInterface $family)
    {
        return $this->doctrine->getRepository($family->getDataClass());
    }
}
