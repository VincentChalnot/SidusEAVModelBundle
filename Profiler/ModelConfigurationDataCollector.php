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

use Sidus\EAVModelBundle\Registry\AttributeTypeRegistry;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * Display model configuration in the debug toolbar
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ModelConfigurationDataCollector extends DataCollector
{
    /**
     * @param FamilyRegistry        $familyRegistry
     * @param AttributeTypeRegistry $attributeTypeRegistry
     */
    public function __construct(FamilyRegistry $familyRegistry, AttributeTypeRegistry $attributeTypeRegistry)
    {
        $this->data = [
            'familyRegistry' => $familyRegistry,
            'attributeTypeRegistry' => $attributeTypeRegistry,
        ];
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
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
    }

    /**
     * @return FamilyRegistry
     */
    public function getFamilyRegistry()
    {
        return $this->data['familyRegistry'];
    }

    /**
     * @return AttributeTypeRegistry
     */
    public function getAttributeTypeRegistry()
    {
        return $this->data['attributeTypeRegistry'];
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
     * @param array $data
     *
     * @return Data|array
     */
    public function wrapData(array $data)
    {
        return $this->cloneVar($data);
    }
}
