<?php

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AttributeCompilerPass implements CompilerPassInterface
{
    /**
     * Inject tagged attributes into configuration handler
     *
     * @param ContainerBuilder $container
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sidus_eav_model.attribute_configuration.handler')) {
            return;
        }

        $definition = $container->findDefinition('sidus_eav_model.attribute_configuration.handler');
        $taggedServices = $container->findTaggedServiceIds('sidus.attribute');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addAttribute',
                [new Reference($id)]
            );
        }
    }
}
