<?php

namespace Sidus\EAVModelBundle;

use Sidus\EAVModelBundle\DependencyInjection\Compiler\AttributeCompilerPass;
use Sidus\EAVModelBundle\DependencyInjection\Compiler\AttributeTypeCompilerPass;
use Sidus\EAVModelBundle\DependencyInjection\Compiler\FamilyCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SidusEAVModelBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into configuration handlers
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AttributeTypeCompilerPass());
        $container->addCompilerPass(new AttributeCompilerPass());
        $container->addCompilerPass(new FamilyCompilerPass());
    }
}
