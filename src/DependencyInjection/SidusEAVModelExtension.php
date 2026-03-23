<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\DependencyInjection;

use Sidus\EAVModelBundle\Attribute\AttributeRegistry;
use Sidus\EAVModelBundle\Bridge\Doctrine\Type\FamilyType;
use Sidus\EAVModelBundle\Context\ContextManagerInterface;
use Sidus\EAVModelBundle\Family\FamilyRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * DI Extension for the EAV Model bundle.
 *
 * Parses configuration and creates services for attributes and families.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusEAVModelExtension extends Extension
{
    /** @var array<string, mixed> */
    protected array $globalConfig = [];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->globalConfig = $config;

        // Set parameters
        $container->setParameter('sidus_eav_model.entity.data.class', $config['data_class']);
        $container->setParameter('sidus_eav_model.entity.value.class', $config['value_class']);
        $container->setParameter('sidus_eav_model.context.default_context', $config['default_context'] ?? []);
        $container->setParameter('sidus_eav_model.context.global_mask', $config['global_context_mask'] ?? []);

        // Register the custom Doctrine type
        $this->registerDoctrineType($container);

        // Load service configuration
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        // Process global attribute configuration
        $attributeRegistryDef = $container->getDefinition(AttributeRegistry::class);
        $attributeRegistryDef->addMethodCall('parseGlobalConfig', [$config['attributes'] ?? []]);

        // Create family services
        $this->createFamilyServices($config, $container);
    }

    public function getAlias(): string
    {
        return 'sidus_eav_model';
    }

    /**
     * Registers the custom Doctrine DBAL type for families.
     */
    private function registerDoctrineType(ContainerBuilder $container): void
    {
        // Get existing Doctrine types or initialize empty array
        $types = [];
        if ($container->hasParameter('doctrine.dbal.connection_factory.types')) {
            $types = $container->getParameter('doctrine.dbal.connection_factory.types');
        }

        // Add our custom type
        $types[FamilyType::TYPE_NAME] = [
            'class' => FamilyType::class,
        ];

        $container->setParameter('doctrine.dbal.connection_factory.types', $types);
    }

    /**
     * Creates service definitions for each configured family.
     *
     * @param array<string, mixed> $config
     */
    private function createFamilyServices(array $config, ContainerBuilder $container): void
    {
        $dataClass = $config['data_class'];
        $valueClass = $config['value_class'];
        $familyClass = $container->getParameter('sidus_eav_model.family.class');

        foreach ($config['families'] ?? [] as $code => $familyConfig) {
            // Use global data/value class if not specified
            $familyConfig['data_class'] = $familyConfig['data_class'] ?? $dataClass;
            $familyConfig['value_class'] = $familyConfig['value_class'] ?? $valueClass;

            $definition = new Definition(
                $familyClass,
                [
                    $code,
                    new Reference(AttributeRegistry::class),
                    new Reference(FamilyRegistry::class),
                    new Reference(ContextManagerInterface::class),
                    $familyConfig['data_class'],
                    $familyConfig['value_class'],
                    $familyConfig,
                ]
            );

            $definition->addMethodCall('setTranslator', [new Reference('translator')]);
            $definition->addTag('sidus.family');

            $container->setDefinition('sidus_eav_model.family.' . $code, $definition);
        }
    }
}
