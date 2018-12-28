<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\DependencyInjection;

use Sidus\EAVModelBundle\Context\ContextManagerInterface;
use Sidus\EAVModelBundle\Doctrine\Types\FamilyType;
use Sidus\EAVModelBundle\Model\Family;
use Sidus\EAVModelBundle\Registry\AttributeRegistry;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Sidus\DataGridBundle\Renderer\RenderableInterface;
use Sidus\DataGridBundle\Renderer\ColumnValueRendererInterface;

/**
 * Parse configuration and creates attributes and families' services
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusEAVModelExtension extends Extension
{
    /** @var array */
    protected $globalConfig;

    /**
     * Generate automatically services for attributes and families from configuration
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->globalConfig = $config;

        $container->setParameter('sidus_eav_model.entity.data.class', $config['data_class']);
        $container->setParameter('sidus_eav_model.entity.value.class', $config['value_class']);
        $container->setParameter('sidus_eav_model.form.collection_type', $config['collection_type']);
        $container->setParameter('sidus_eav_model.context.form_type', $config['context_form_type']);
        $container->setParameter('sidus_eav_model.context.default_context', $config['default_context']);
        $container->setParameter('sidus_eav_model.context.global_mask', $config['global_context_mask']);

        // Injecting custom doctrine type
        $doctrineTypes = $container->getParameter('doctrine.dbal.connection_factory.types');
        $doctrineTypes['sidus_family'] = ['class' => FamilyType::class, 'commented' => true];
        $container->setParameter('doctrine.dbal.connection_factory.types', $doctrineTypes);

        // Load services config
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('attribute_types.yml');
        $loader->load('command.yml');
        $loader->load('configuration.yml');
        $loader->load('context.yml');
        $loader->load('debug.yml');
        $loader->load('deprecated.yml');
        $loader->load('doctrine.yml');
        $loader->load('entities.yml');
        $loader->load('events.yml');
        $loader->load('forms.yml');
        $loader->load('manager.yml');
        $loader->load('param_converters.yml');
        $loader->load('twig.yml');
        $loader->load('validators.yml');

        if ($config['serializer_enabled']) {
            // Only load normalizers if symfony serializer is loaded
            $loader->load('serializer.yml');
            $loader->load('normalizer.yml');
            $loader->load('denormalizer.yml');
            $loader->load('deprecated_serializer.yml');
        }

        // The DatagridBundle might be installed
        /** @noinspection PhpUndefinedClassInspection */
        if (interface_exists(RenderableInterface::class)) {
            $loader->load('datagrid.yml');
        }
        /** @noinspection PhpUndefinedClassInspection */
        if (interface_exists(ColumnValueRendererInterface::class)) {
            $loader->load('datagrid-v2.yml');
        }

        // Add global attribute configuration to handler
        $attributeConfiguration = $container->getDefinition(AttributeRegistry::class);
        $attributeConfiguration->addMethodCall('parseGlobalConfig', [$config['attributes']]);

        $this->createFamilyServices($config, $container);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    protected function createFamilyServices(array $config, ContainerBuilder $container)
    {
        // Automatically declare a service for each family configured
        foreach ((array) $config['families'] as $code => $familyConfiguration) {
            if (empty($familyConfiguration['data_class'])) {
                $familyConfiguration['data_class'] = $config['data_class'];
            }
            if (empty($familyConfiguration['value_class'])) {
                $familyConfiguration['value_class'] = $config['value_class'];
            }
            if (empty($familyConfiguration['family_class'])) {
                $familyConfiguration['family_class'] = $config['family_class'];
            }
            $this->addFamilyServiceDefinition($code, $familyConfiguration, $container);
        }
    }

    /**
     * @param string           $code
     * @param array            $familyConfiguration
     * @param ContainerBuilder $container
     *
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    protected function addFamilyServiceDefinition($code, $familyConfiguration, ContainerBuilder $container)
    {
        $class = $familyConfiguration['family_class'];
        unset($familyConfiguration['family_class']);
        $definition = new Definition(
            $class,
            [
                $code,
                new Reference(AttributeRegistry::class),
                new Reference(FamilyRegistry::class),
                new Reference(ContextManagerInterface::class),
                $familyConfiguration,
            ]
        );
        if (is_a($class, Family::class, true)) {
            $definition->addMethodCall('setTranslator', [new Reference('translator')]);
        }
        $definition->addTag('sidus.family');
        $container->setDefinition('sidus_eav_model.family.'.$code, $definition);
    }
}
