<?php

namespace Sidus\EAVModelBundle;

use Sidus\EAVModelBundle\DependencyInjection\Compiler\GenericCompilerPass;
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
        $container->addCompilerPass(new GenericCompilerPass(
            'sidus_eav_model.attribute_type_configuration.handler',
            'sidus.attribute_type',
            'addType'));
        $container->addCompilerPass(new GenericCompilerPass(
            'sidus_eav_model.attribute_configuration.handler',
            'sidus.attribute',
            'addAttribute'));
        $container->addCompilerPass(new GenericCompilerPass(
            'sidus_eav_model.family_configuration.handler',
            'sidus.family',
            'addFamily'));
    }
}
