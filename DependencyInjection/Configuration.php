<?php

namespace Sidus\EAVModelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Build extendable configuration tree
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sidus_eav_model');
        /** @var NodeBuilder $attributeDefinition */
        $attributeDefinition = $rootNode
            ->children()
                ->scalarNode('data_class')->isRequired()->end()
                ->scalarNode('value_class')->isRequired()->end()
                ->scalarNode('collection_type')->defaultValue('collection')->end()
                ->scalarNode('context_form_type')->defaultNull()->end()
                ->variableNode('default_context')->end()
                ->arrayNode('global_context_mask')
                    ->prototype('scalar')->end()
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
     * @param NodeBuilder $attributeDefinition
     */
    protected function appendAttributeDefinition(NodeBuilder $attributeDefinition)
    {
        $attributeDefinition
            ->scalarNode('type')->end()
            ->scalarNode('group')->end()
            ->scalarNode('family')->end()
            ->scalarNode('form_type')->end()
            ->arrayNode('families')
                ->prototype('scalar')->end()
            ->end()
            ->variableNode('options')->end()
            ->variableNode('form_options')->end()
            ->variableNode('view_options')->end()
            ->variableNode('validation_rules')->end()
            ->variableNode('default')->end()
            ->booleanNode('required')->defaultValue(false)->end()
            ->booleanNode('unique')->defaultValue(false)->end()
            ->booleanNode('multiple')->defaultValue(false)->end()
            ->arrayNode('context_mask')
                ->prototype('scalar')->end()
            ->end();
    }


    /**
     * @param NodeBuilder $familyDefinition
     */
    protected function appendFamilyDefinition(NodeBuilder $familyDefinition)
    {
        /** @var NodeBuilder $attributeDefinition */
        $attributeDefinition = $familyDefinition
            ->scalarNode('data_class')->end()
            ->scalarNode('value_class')->end()
            ->scalarNode('label')->defaultNull()->end()
            ->variableNode('options')->end()
            ->scalarNode('attributeAsLabel')->defaultNull()->end()
            ->scalarNode('attributeAsIdentifier')->defaultNull()->end()
            ->scalarNode('parent')->end()
            ->booleanNode('singleton')->defaultValue(false)->end()
            ->booleanNode('instantiable')->defaultValue(true)->end()
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
}
