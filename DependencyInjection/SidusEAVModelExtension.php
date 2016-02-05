<?php

namespace Sidus\EAVModelBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
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
     * @throws \Exception
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sidus_eav_model.entity.data.class', $config['data_class']);
        $container->setParameter('sidus_eav_model.entity.value.class', $config['value_class']);
        $container->setParameter('sidus_eav_model.form.collection_type', $config['collection_type']);

        // Automatically declare a service for each attribute configured
        foreach ($config['attributes'] as $code => $attributeConfiguration) {
            $this->addAttributeServiceDefinition($code, $attributeConfiguration, $container);
        }

        // Automatically declare a service for each family configured
        foreach ($config['families'] as $code => $familyConfiguration) {
            if (empty($familyConfiguration['data_class'])) {
                $familyConfiguration['data_class'] = $config['data_class'];
            }
            if (empty($familyConfiguration['value_class'])) {
                $familyConfiguration['value_class'] = $config['value_class'];
            }
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
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    protected function addFamilyServiceDefinition($code, $familyConfiguration, ContainerBuilder $container)
    {
        $definition = new Definition(new Parameter('sidus_eav_model.family.class'), [
            $code,
            new Reference('sidus_eav_model.attribute_configuration.handler'),
            new Reference('sidus_eav_model.family_configuration.handler'),
            $familyConfiguration,
        ]);
        $definition->addMethodCall('setTranslator', [new Reference('translator')]);
        $definition->addTag('sidus.family');
        $container->setDefinition('sidus_eav_model.family.' . $code, $definition);
    }

    /**
     * @param string $code
     * @param array $attributeConfiguration
     * @param ContainerBuilder $container
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    protected function addAttributeServiceDefinition($code, $attributeConfiguration, ContainerBuilder $container)
    {
        $definition = new Definition(new Parameter('sidus_eav_model.attribute.class'), [
            $code,
            new Reference('sidus_eav_model.attribute_type_configuration.handler'),
            $attributeConfiguration,
        ]);
        $definition->addMethodCall('setTranslator', [new Reference('translator')]);
        $definition->addTag('sidus.attribute');
        $container->setDefinition('sidus_eav_model.attribute.' . $code, $definition);
    }
}
