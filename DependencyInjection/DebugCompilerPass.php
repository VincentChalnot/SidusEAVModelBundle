<?php

namespace Sidus\EAVModelBundle\DependencyInjection;


use Sidus\EAVModelBundle\Debug\EAVDataCaster;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DebugCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('var_dumper.cloner')->addMethodCall('addCasters', [
            [DataInterface::class => [new Reference(EAVDataCaster::class), 'castDataInterface']],
        ]);
    }

}
