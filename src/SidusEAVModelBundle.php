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

namespace Sidus\EAVModelBundle;

use Sidus\EAVModelBundle\Attribute\AttributeRegistry;
use Sidus\EAVModelBundle\Attribute\AttributeTypeRegistry;
use Sidus\EAVModelBundle\DependencyInjection\Compiler\AttributeCompilerPass;
use Sidus\EAVModelBundle\DependencyInjection\Compiler\AttributeTypeCompilerPass;
use Sidus\EAVModelBundle\DependencyInjection\Compiler\FamilyCompilerPass;
use Sidus\EAVModelBundle\Family\FamilyRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Main bundle class for Sidus EAV Model.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusEAVModelBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register compiler passes for tagged services
        $container->addCompilerPass(new AttributeTypeCompilerPass());
        $container->addCompilerPass(new AttributeCompilerPass());
        $container->addCompilerPass(new FamilyCompilerPass());
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
