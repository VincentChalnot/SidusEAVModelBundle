<?php

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FilterCompilerPass implements CompilerPassInterface
{
    /**
     * Inject tagged filters into configuration handler
     *
     * @param ContainerBuilder $container
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sidus_eav_model.filter_configuration.handler')) {
            return;
        }

        $definition = $container->findDefinition('sidus_eav_model.filter_configuration.handler');
        $taggedServices = $container->findTaggedServiceIds('sidus.filter');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addFilter',
                [new Reference($id)]
            );
        }
    }
}
