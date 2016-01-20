<?php

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class FamilyCompilerPass implements CompilerPassInterface
{
    /**
     * Inject tagged families into configuration handler
     *
     * @param ContainerBuilder $container
     * @api
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sidus_eav_model.family_configuration.handler')) {
            return;
        }

        $definition = $container->findDefinition('sidus_eav_model.family_configuration.handler');
        $taggedServices = $container->findTaggedServiceIds('sidus.family');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addFamily',
                [new Reference($id)]
            );
        }
    }
}
