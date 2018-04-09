<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle;

use Sidus\BaseBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusEAVModelBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into configuration handlers
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new GenericCompilerPass(
                'sidus_eav_model.attribute_type.registry',
                'sidus.attribute_type',
                'addType'
            )
        );
        $container->addCompilerPass(
            new GenericCompilerPass(
                'sidus_eav_model.attribute.registry',
                'sidus.attribute',
                'addAttribute'
            )
        );
        $container->addCompilerPass(
            new GenericCompilerPass(
                'sidus_eav_model.family.registry',
                'sidus.family',
                'addFamily'
            )
        );
    }
}
