<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

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
                ->booleanNode('serializer_enabled')->defaultFalse()->end()
                ->scalarNode('collection_type')->defaultValue(CollectionType::class)->end()
                ->scalarNode('context_form_type')->defaultNull()->end()
                ->variableNode('default_context')->defaultValue([])->end()
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
            ->scalarNode('form_type')->end()
            ->variableNode('form_options')->end()
            ->variableNode('options')->end()
            ->variableNode('validation_rules')->end()
            ->variableNode('default')->end()
            ->booleanNode('required')->defaultValue(false)->end()
            ->booleanNode('unique')->defaultValue(false)->end()
            ->booleanNode('multiple')->defaultValue(false)->end()
            ->booleanNode('collection')->end()
            ->variableNode('context_mask')->defaultNull()->end();
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
