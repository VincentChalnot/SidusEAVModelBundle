<?php

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class AttributeTypeCompilerPass implements CompilerPassInterface
{
    /**
     * Inject tagged attribute types into configuration handler
     *
     * @param ContainerBuilder $container
     * @api
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sidus_eav_model.attribute_type_configuration.handler')) {
            return;
        }

        $definition = $container->findDefinition('sidus_eav_model.attribute_type_configuration.handler');
        $taggedServices = $container->findTaggedServiceIds('sidus.attribute_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addType',
                [new Reference($id)]
            );
        }
    }
}
