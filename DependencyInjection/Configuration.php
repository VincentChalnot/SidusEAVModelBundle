<?php

namespace Sidus\EAVModelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Reference;

class Configuration implements ConfigurationInterface
{
    /**
     * @todo Build prototype for families
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sidus_eav_model');
        $rootNode
            ->children()
                ->scalarNode('data_class')->isRequired()->end()
                ->scalarNode('value_class')->isRequired()->end()
                ->scalarNode('collection_type')->defaultValue('collection')->end()
                ->arrayNode('attributes')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->defaultValue('string')->end()
                            ->scalarNode('group')->end()
                            ->variableNode('form_options')->end()
                            ->variableNode('view_options')->end()
                            ->variableNode('validation_rules')->end()
                            ->booleanNode('required')->defaultValue(false)->end()
                            ->booleanNode('unique')->defaultValue(false)->end()
                            ->booleanNode('multiple')->defaultValue(false)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('families')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('value_class')->end()
                            ->scalarNode('attributeAsLabel')->defaultValue('string')->end()
                            ->scalarNode('parent')->end()
                            ->booleanNode('instantiable')->defaultValue(true)->end()
                            ->arrayNode('attributes')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
