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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration schema for the EAV Model bundle.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sidus_eav_model');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('data_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('The fully qualified class name of your Data entity')
                ->end()
                ->scalarNode('value_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('The fully qualified class name of your Value entity')
                ->end()
                ->arrayNode('default_context')
                    ->prototype('variable')->end()
                    ->info('Default context values (e.g., locale, channel)')
                ->end()
                ->arrayNode('global_context_mask')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                    ->info('Context keys that apply to all attributes by default')
                ->end()
            ->end();

        $this->addAttributesSection($rootNode);
        $this->addFamiliesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds the attributes configuration section.
     */
    private function addAttributesSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('attributes')
                    ->useAttributeAsKey('code')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->defaultValue('string')->end()
                            ->scalarNode('label')->defaultNull()->end()
                            ->scalarNode('group')->defaultNull()->end()
                            ->booleanNode('required')->defaultFalse()->end()
                            ->booleanNode('unique')->defaultFalse()->end()
                            ->booleanNode('collection')->defaultFalse()->end()
                            ->variableNode('default')->defaultNull()->end()
                            ->arrayNode('context_mask')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('validation_rules')
                                ->prototype('array')
                                    ->prototype('variable')->end()
                                ->end()
                            ->end()
                            ->arrayNode('options')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->info('Global attribute definitions that can be reused across families')
                ->end()
            ->end();
    }

    /**
     * Adds the families configuration section.
     */
    private function addFamiliesSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('families')
                    ->useAttributeAsKey('code')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')->defaultNull()->end()
                            ->scalarNode('type')->defaultNull()->end()
                            ->scalarNode('data_class')->defaultNull()->end()
                            ->scalarNode('value_class')->defaultNull()->end()
                            ->scalarNode('parent')->defaultNull()->end()
                            ->scalarNode('attributeAsLabel')->defaultNull()->end()
                            ->scalarNode('attributeAsIdentifier')->defaultNull()->end()
                            ->booleanNode('instantiable')->defaultTrue()->end()
                            ->booleanNode('singleton')->defaultFalse()->end()
                            ->arrayNode('options')
                                ->prototype('variable')->end()
                            ->end()
                            ->arrayNode('attributes')
                                ->useAttributeAsKey('code')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('type')->end()
                                        ->scalarNode('label')->end()
                                        ->scalarNode('group')->end()
                                        ->booleanNode('required')->end()
                                        ->booleanNode('unique')->end()
                                        ->booleanNode('collection')->end()
                                        ->variableNode('default')->end()
                                        ->arrayNode('context_mask')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->arrayNode('validation_rules')
                                            ->prototype('array')
                                                ->prototype('variable')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('options')
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->info('Family definitions')
                ->end()
            ->end();
    }
}
