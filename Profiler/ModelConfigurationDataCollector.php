<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Profiler;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\AttributeTypeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\AttributeTypeRegistry;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Display model configuration in the debug toolbar
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ModelConfigurationDataCollector extends DataCollector
{
    /** @var array[] */
    protected $data = [
        'families' => [],
        'attributeTypes' => [],
    ];

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /**
     * @param FamilyRegistry        $familyRegistry
     * @param AttributeTypeRegistry $attributeTypeRegistry
     */
    public function __construct(FamilyRegistry $familyRegistry, AttributeTypeRegistry $attributeTypeRegistry)
    {
        $this->familyRegistry = $familyRegistry;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request         $request
     * @param Response        $response
     * @param \Exception|null $exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        foreach ($this->familyRegistry->getFamilies() as $family) {
            $this->data['families'][$family->getCode()] = $this->parseFamily($family);
        }
        foreach ($this->attributeTypeRegistry->getTypes() as $attributeType) {
            $this->data['attributeTypes'][$attributeType->getCode()] = $this->parseAttributeType($attributeType);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [
            'families' => [],
            'attributeTypes' => [],
        ];
    }

    /**
     * @return array[]
     */
    public function getFamilies()
    {
        return $this->data['families'];
    }

    /**
     * @return array[]
     */
    public function getAttributeTypes()
    {
        return $this->data['attributeTypes'];
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return 'sidus_eav_model';
    }

    /**
     * @param FamilyInterface|null $family
     *
     * @return array|null
     */
    protected function parseFamily(FamilyInterface $family = null)
    {
        if (null === $family) {
            return null;
        }

        return [
            'code' => $family->getCode(),
            'label' => $family->getLabel(),
            'instantiable' => $family->isInstantiable(),
            'singleton' => $family->isSingleton(),
            'parent' => $family->getParent() ? [
                'code' => $family->getParent()->getCode(),
                'label' => $family->getParent()->getLabel(),
            ] : null,
            'dataClass' => $family->getDataClass(),
            'valueClass' => $family->getValueClass(),
            'attributeAsIdentifier' => $this->parseAttribute($family->getAttributeAsIdentifier()),
            'attributeAsLabel' => $this->parseAttribute($family->getAttributeAsLabel()),
            'attributes' => array_map([$this, 'parseAttribute'], $family->getAttributes()),
            'data_class' => $family->getDataClass(),
        ];
    }

    /**
     * @param AttributeInterface|null $attribute
     *
     * @return array|null
     */
    protected function parseAttribute(AttributeInterface $attribute = null)
    {
        if (null === $attribute) {
            return null;
        }

        return [
            'code' => $attribute->getCode(),
            'label' => $attribute->getLabel(),
            'group' => $attribute->getGroup(),
            'type' => $this->parseAttributeType($attribute->getType()),
            'required' => $attribute->isRequired(),
            'unique' => $attribute->isUnique(),
            'multiple' => $attribute->isMultiple(),
            'collection' => $attribute->isCollection(),
            'contextMask' => $attribute->getContextMask(),
            'validationRules' => $this->cloneVar($attribute->getValidationRules()),
            'options' => $this->cloneVar($attribute->getOptions()),
            'formOptions' => $this->cloneVar($attribute->getFormOptions()),
        ];
    }

    /**
     * @param AttributeTypeInterface|null $attributeType
     *
     * @return array|null
     */
    protected function parseAttributeType(AttributeTypeInterface $attributeType = null)
    {
        if (null === $attributeType) {
            return null;
        }

        return [
            'code' => $attributeType->getCode(),
            'relation' => $attributeType->isRelation(),
            'embedded' => $attributeType->isEmbedded(),
            'databaseType' => $attributeType->getDatabaseType(),
            'formType' => $attributeType->getFormType(),
        ];
    }
}
