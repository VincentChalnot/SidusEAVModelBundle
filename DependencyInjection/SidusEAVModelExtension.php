<?php

namespace Sidus\EAVModelBundle\DependencyInjection;

use Sidus\EAVModelBundle\Doctrine\Types\FamilyType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
     * {@inheritdoc}
     * @throws \Exception
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
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

        // Injecting custom doctrine type
        $doctrineTypes = $container->getParameter('doctrine.dbal.connection_factory.types');
        $doctrineTypes['sidus_family'] = ['class' => FamilyType::class, 'commented' => true];
        $container->setParameter('doctrine.dbal.connection_factory.types', $doctrineTypes);

        // Automatically declare a service for each attribute configured
        foreach ((array) $config['attributes'] as $code => $attributeConfiguration) {
            $this->addAttributeServiceDefinition($code, $attributeConfiguration, $container);
        }

        $this->createFamilyServices($config, $container);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('attribute_types.yml');
        $loader->load('configuration.yml');
        $loader->load('context.yml');
        $loader->load('entities.yml');
        $loader->load('forms.yml');
        $loader->load('param_converters.yml');
        $loader->load('validators.yml');
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
        $definition = new Definition(
            new Parameter('sidus_eav_model.family.class'), [
            $code,
            new Reference('sidus_eav_model.attribute_configuration.handler'),
            new Reference('sidus_eav_model.family_configuration.handler'),
            new Reference('sidus_eav_model.context.manager'),
            $familyConfiguration,
        ]
        );
        $definition->addMethodCall('setTranslator', [new Reference('translator')]);
        $definition->addTag('sidus.family');
        $container->setDefinition('sidus_eav_model.family.'.$code, $definition);
    }

    /**
     * @param string           $code
     * @param array            $attributeConfiguration
     * @param ContainerBuilder $container
     *
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    protected function addAttributeServiceDefinition($code, $attributeConfiguration, ContainerBuilder $container)
    {
        $attributeConfiguration['context_mask'] = array_merge(
            $this->globalConfig['global_context_mask'],
            $attributeConfiguration['context_mask']
        );

        $definitionOptions = [
            $code,
            new Reference('sidus_eav_model.attribute_type_configuration.handler'),
            $attributeConfiguration,
        ];
        $definition = new Definition(new Parameter('sidus_eav_model.attribute.class'), $definitionOptions);
        $definition->addMethodCall('setTranslator', [new Reference('translator')]);
        $definition->addTag('sidus.attribute');
        $container->setDefinition('sidus_eav_model.attribute.'.$code, $definition);
    }
}
