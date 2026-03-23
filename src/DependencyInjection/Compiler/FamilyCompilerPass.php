<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Sidus\EAVModelBundle\Family\FamilyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register families.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(FamilyRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(FamilyRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('sidus.family');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addFamily', [new Reference($id)]);
        }
    }
}
