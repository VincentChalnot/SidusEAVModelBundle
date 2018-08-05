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

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Build extendable configuration tree
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Configuration implements ConfigurationInterface
{
    /** @var string */
    protected $root;

    /**
     * @param string $root
     */
    public function __construct(string $root = 'sidus_eav_model')
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);
        /** @var NodeBuilder $attributeDefinition */
        $attributeDefinition = $rootNode
            ->children()
                ->scalarNode('data_class')->isRequired()->end()
                ->scalarNode('value_class')->isRequired()->end()
                ->variableNode('default_context')->defaultValue([])->end()
                ->arrayNode('global_context_mask')
                    ->prototype('scalar')->defaultValue([])->end()
                ->end()
                ->arrayNode('attributes')
                    ->useAttributeAsKey('code')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->cannotBeOverwritten()
                        ->children();

        $this->appendAttributeDefinition($attributeDefinition);

        /** @var NodeBuilder $familyDefinition */
        $familyDefinition = $attributeDefinition
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('families')
                    ->useAttributeAsKey('code')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->cannotBeOverwritten()
                        ->children();

        $this->appendFamilyDefinition($familyDefinition);

        $familyDefinition
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $familyDefinition
     */
    protected function appendFamilyDefinition(NodeBuilder $familyDefinition): void
    {
        /** @var NodeBuilder $attributeDefinition */
        $attributeDefinition = $familyDefinition
            ->scalarNode('data_class')->end()
            ->scalarNode('value_class')->end()
            ->variableNode('options')->end()
            ->scalarNode('attributeAsLabel')->end()
            ->scalarNode('attributeAsIdentifier')->end()
            ->scalarNode('parent')->end()
            ->variableNode('behaviors')->end()
            ->variableNode('events')->end()
            ->booleanNode('singleton')->end()
            ->booleanNode('instantiable')->end()
            ->arrayNode('attributes')
                ->useAttributeAsKey('code')
                ->prototype('array')
                    ->performNoDeepMerging()
                    ->cannotBeOverwritten()
                    ->children();

        $this->appendAttributeDefinition($attributeDefinition);

        $attributeDefinition
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $attributeDefinition
     */
    protected function appendAttributeDefinition(NodeBuilder $attributeDefinition): void
    {
        $attributeDefinition
            ->scalarNode('type')->end()
            ->scalarNode('group')->end()
            ->variableNode('options')->end()
            ->variableNode('validation_rules')->end()
            ->variableNode('default')->end()
            ->variableNode('values')->end()
            ->booleanNode('required')->end()
            ->booleanNode('unique')->end()
            ->booleanNode('collection')->end()
            ->variableNode('context_mask')->end();
    }
}
