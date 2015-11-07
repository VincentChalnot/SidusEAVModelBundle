<?php

namespace Sidus\EAVModelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @todo Build prototype for families
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sidus_eav_model');
        $rootNode
            ->children()
            ->arrayNode('attributes')
            ->prototype('array')
            ->children()
            ->scalarNode('type')->defaultValue('string')->end()
            ->variableNode('form_options')->end()
            ->variableNode('view_options')->end()
            ->booleanNode('versionable')->defaultValue(false)->end()
            ->booleanNode('required')->defaultValue(false)->end()
            ->booleanNode('unique')->defaultValue(false)->end()
            ->booleanNode('multiple')->defaultValue(false)->end()
            ->booleanNode('scopable')->defaultValue(false)->end()
            ->booleanNode('translatable')->defaultValue(false)->end()
            ->booleanNode('country_specific')->defaultValue(false)->end()
            ->booleanNode('localizable')->defaultValue(false)->end()
            ->end()
            ->end()
            ->end()
            ->variableNode('families')->end()
            ->end();

        return $treeBuilder;
    }
}
