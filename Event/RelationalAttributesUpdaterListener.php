<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Event;

use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;

/**
 * Maintains a bidirectional relation between two attributes from different families, listens to child data changes and
 * automatically updates the related parent data attribute
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class RelationalAttributesUpdaterListener
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var AttributeInterface[][] */
    protected $relationsAttributes;

    /** @var AttributeInterface[][] */
    protected $cascadeDeleteAttributes = [];

    /** @var string */
    protected $dataClass;

    /**
     * @param FamilyRegistry $familyRegistry
     * @param string         $dataClass
     */
    public function __construct(FamilyRegistry $familyRegistry, string $dataClass)
    {
        $this->familyRegistry = $familyRegistry;
        $this->dataClass = $dataClass;
    }

    /**
     * @param EAVEvent $event
     */
    public function onSidusEavData(EAVEvent $event)
    {
        $data = $event->getData();

        // Case where a data pointed by a relation attribute is changed
        if (\array_key_exists($data->getFamilyCode(), $this->getRelationsAttributes())) {
            if (EAVEvent::STATE_REMOVED === $event->getState()) {
                return; // Related values are removed automatically with cascade=delete
            }
            foreach ($this->getRelationsAttributes()[$data->getFamilyCode()] as $attributeCode => $parentAttribute) {
                $parentData = $data->get($attributeCode);
                if (null !== $parentData) {
                    $this->updateParentData($event, $parentData, $parentAttribute);
                }
            }
        }

        // Case where a data having a relation attribute is changed, check for cascade delete option
        if (EAVEvent::STATE_REMOVED === $event->getState()
            && \array_key_exists($data->getFamilyCode(), $this->getCascadeDeleteAttributes())) {
            foreach ($this->getCascadeDeleteAttributes()[$data->getFamilyCode()] as $attributeCode => $attribute) {
                $this->deleteCascadeChildrenData($event, $data, $attribute);
            }
        }
    }

    /**
     * @param EAVEvent           $event
     * @param DataInterface      $parentData
     * @param AttributeInterface $parentAttribute
     */
    protected function updateParentData(
        EAVEvent $event,
        DataInterface $parentData,
        AttributeInterface $parentAttribute
    ) {
        $targetFamilies = [];
        if (isset($parentAttribute->getOption('relations')['families'])) {
            $targetFamilies = $parentAttribute->getOption('relations')['families'];
        }
        $targetEntities = $this->fetchTargetEntities($event, $parentData, $targetFamilies);

        // Update parent collection
        $parentData->set($parentAttribute->getCode(), $targetEntities);

        // If child entity was just created then it's not part of the target entities fetched from database,
        // so add it manually to parent collection
        $currentData = $event->getData();
        if (array_key_exists($currentData->getFamilyCode(), $targetFamilies)
            && EAVEvent::STATE_CREATED === $event->getState()) {
            $parentData->add($parentAttribute->getCode(), $currentData);
        }

        $event->recomputeAttributeChangeset($parentData, $parentAttribute);
    }

    /**
     * @param EAVEvent           $event
     * @param DataInterface      $parentData
     * @param AttributeInterface $parentAttribute
     */
    protected function deleteCascadeChildrenData(
        EAVEvent $event,
        DataInterface $parentData,
        AttributeInterface $parentAttribute
    ) {
        $targetFamilies = [];
        if (isset($parentAttribute->getOption('relations')['families'])) {
            $targetFamilies = $parentAttribute->getOption('relations')['families'];
        }
        $targetEntities = $this->fetchTargetEntities($event, $parentData, $targetFamilies);

        foreach ($targetEntities as $targetEntity) {
            $event->getEntityManager()->remove($targetEntity);
        }
    }

    /**
     * @return AttributeInterface[][]
     */
    protected function getRelationsAttributes()
    {
        $this->buildRelationsMap();

        return $this->relationsAttributes;
    }

    /**
     * @return AttributeInterface[][]
     */
    protected function getCascadeDeleteAttributes()
    {
        $this->buildRelationsMap();

        return $this->cascadeDeleteAttributes;
    }

    /**
     * Build the internal "map" from the model configuration
     */
    protected function buildRelationsMap()
    {
        // If internal cache already build, skip
        if (null !== $this->relationsAttributes) {
            return;
        }
        // iterates over every attributes of every families (@todo: Use a different pattern? Add cache?)
        $this->relationsAttributes = [];
        foreach ($this->familyRegistry->getFamilies() as $family) {
            foreach ($family->getAttributes() as $attribute) {
                $this->buildAttributeRelations($attribute);
            }
        }
    }

    /**
     * @param AttributeInterface $attribute
     */
    protected function buildAttributeRelations(AttributeInterface $attribute)
    {
        $relations = $attribute->getOption('relations');
        if (null === $relations) {
            return;
        }
        if (isset($relations['cascade_delete']) ? $relations['cascade_delete'] : false) {
            $this->cascadeDeleteAttributes[$attribute->getFamily()->getCode()][$attribute->getCode()] = $attribute;
        }
        if (!array_key_exists('families', $relations)) {
            throw new \UnexpectedValueException(
                "Missing configuration option: 'options.relations.families' for attribute: {$attribute->getCode()}"
            );
        }
        foreach ($relations['families'] as $familyCode => $reverseAttributeCode) {
            if (isset($this->relationsAttributes[$familyCode][$reverseAttributeCode])) {
                throw new \LogicException(
                    "Reverse relation already defined for {$familyCode}.{$reverseAttributeCode}"
                );
            }
            $this->relationsAttributes[$familyCode][$reverseAttributeCode] = $attribute;
        }
    }

    /**
     * @param EAVEvent      $event
     * @param DataInterface $parentData
     * @param array         $targetFamilies
     *
     * @return DataInterface[]
     */
    protected function fetchTargetEntities(EAVEvent $event, DataInterface $parentData, array $targetFamilies)
    {
        $entityManager = $event->getEntityManager();
        /** @var DataRepository $repository */
        $repository = $entityManager->getRepository($this->dataClass);
        $results = [];
        foreach ($targetFamilies as $targetFamilyCode => $targetAttributeCode) {
            $eavQb = $repository->createEAVQueryBuilder();
            $targetFamily = $this->familyRegistry->getFamily($targetFamilyCode);

            $qb = $eavQb->apply(
                $eavQb
                    ->attribute($targetFamily->getAttribute($targetAttributeCode))
                    ->equals($parentData)
            );
            $qb->select("{$eavQb->getAlias()}.id");
            foreach ($qb->getQuery()->getResult() as $item) {
                $results[] = $entityManager->getReference($targetFamily->getDataClass(), $item['id']);
            }
        }

        return $results;
    }
}
