<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Sidus\EAVModelBundle\Doctrine\AttributeQueryBuilderInterface;
use Sidus\EAVModelBundle\Doctrine\EAVQueryBuilderInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;

/**
 * Dedicated class to manage data when the Repository is not enough.
 * Because of Doctrine's internal, we can't properly inject services inside repositories so all methods that depends on
 * other services are moved here.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataManager
{
    /** @var DataRepository */
    protected $repository;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /**
     * @param FamilyRegistry $familyRegistry
     * @param Registry       $doctrine
     * @param string         $dataClass
     */
    public function __construct(FamilyRegistry $familyRegistry, Registry $doctrine, $dataClass)
    {
        $this->familyRegistry = $familyRegistry;
        $this->repository = $doctrine->getRepository($dataClass);
    }

    /**
     * This method was moved from the DataRepository because it depends on the FamilyRegistry to resolve sub families
     *
     * @param FamilyInterface[] $families
     * @param string            $term
     *
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \LogicException
     * @throws \UnexpectedValueException
     *
     * @return QueryBuilder
     */
    public function getQbForFamiliesAndLabel(array $families, $term)
    {
        // Removing empty terms with wildcards on both sides
        if ('%%' === $term) {
            $term = '%';
        }

        // Specific optimisation for match-all queries
        if ('%' === $term) {
            return $this->repository->getQbForFamilies($families);
        }

        $eavQb = $this->repository->createEAVQueryBuilder();
        $orCondition = [];

        foreach ($families as $family) {
            $eavQbAttributes = $this->resolveEavQbAttributes($family, $eavQb);
            foreach ($eavQbAttributes as $eavQbAttribute) {
                $orCondition[] = $eavQbAttribute->like($term);
            }
        }

        return $eavQb->apply($eavQb->getOr($orCondition));
    }

    /**
     * @param FamilyInterface                     $family
     * @param EAVQueryBuilderInterface            $eavQb
     * @param AttributeQueryBuilderInterface|null $parentAttributeQb
     *
     * @throws \LogicException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     *
     * @return AttributeQueryBuilderInterface[]
     */
    public function resolveEavQbAttributes(
        FamilyInterface $family,
        EAVQueryBuilderInterface $eavQb,
        AttributeQueryBuilderInterface $parentAttributeQb = null
    ) {
        $attribute = $family->getAttributeAsLabel();
        if (!$attribute) {
            throw new \LogicException("Family {$family->getCode()} does not have an attribute as label");
        }

        if ($parentAttributeQb) {
            $attributeQb = $parentAttributeQb->join()->attribute($attribute);
        } else {
            $attributeQb = $eavQb->attribute($attribute);
        }
        $eavQbAttributes = [$attributeQb];

        $attributeType = $attribute->getType();
        if ($attributeType->isRelation() || $attributeType->isEmbedded()) {
            $eavQbAttributes = [];
            foreach ((array) $attribute->getOption('allowed_families', []) as $subFamily) {
                if (!$subFamily instanceof FamilyInterface) {
                    $subFamily = $this->familyRegistry->getFamily($subFamily);
                }
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $eavQbAttributes = array_merge(
                    $eavQbAttributes,
                    $this->resolveEavQbAttributes(
                        $subFamily,
                        $eavQb,
                        $attributeQb
                    )
                );
            }
        }

        return $eavQbAttributes;
    }
}
