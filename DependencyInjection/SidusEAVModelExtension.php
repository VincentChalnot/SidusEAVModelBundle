<?php

namespace Sidus\EAVModelBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SidusEAVModelExtension extends Extension
{
    /**
     * Generate automatically services for attributes and families from configuration
     *
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Automatically declare a service for each attribute configured
        foreach ($config['attributes'] as $code => $attributeConfiguration) {
            $this->addAttributeServiceDefinition($code, $attributeConfiguration, $container);
        }

        // Automatically declare a service for each family configured
        foreach ($config['families'] as $code => $familyConfiguration) {
            $this->addFamilyServiceDefinition($code, $familyConfiguration, $container);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('attribute_types.yml');
        $loader->load('forms.yml');
    }

    /**
     * @param string $code
     * @param array $familyConfiguration
     * @param ContainerBuilder $container
     */
    protected function addFamilyServiceDefinition($code, $familyConfiguration, ContainerBuilder $container)
    {
        $definition = new Definition(new Parameter('sidus_eav_model.family.class'), [
            $code,
            new Reference('sidus_eav_model.attribute_configuration.handler'),
            new Reference('sidus_eav_model.family_configuration.handler'),
            $familyConfiguration,
        ]);
        $definition->addTag('sidus.family');
        $container->setDefinition('sidus_eav_model.family.' . $code, $definition);
    }

    /**
     * @param string $code
     * @param array $attributeConfiguration
     * @param ContainerBuilder $container
     */
    protected function addAttributeServiceDefinition($code, $attributeConfiguration, ContainerBuilder $container)
    {
        $definition = new Definition(new Parameter('sidus_eav_model.attribute.class'), [
            $code,
            new Reference('sidus_eav_model.attribute_type_configuration.handler'),
            $attributeConfiguration,
        ]);
        $definition->addTag('sidus.attribute');
        $container->setDefinition('sidus_eav_model.attribute.' . $code, $definition);
    }
}
