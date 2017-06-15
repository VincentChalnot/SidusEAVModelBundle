<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Generic compiler pass to add tagged services to another service
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class GenericCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $registry;

    /** @var string */
    protected $tag;

    /** @var string */
    protected $method;

    /**
     * @param string $registry
     * @param string $tag
     * @param string $method
     */
    public function __construct($registry, $tag, $method)
    {
        $this->registry = $registry;
        $this->tag = $tag;
        $this->method = $method;
    }

    /**
     * Inject tagged services into defined registry
     *
     * @api
     *
     * @param ContainerBuilder $container
     *
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has($this->registry)) {
            return;
        }

        $definition = $container->findDefinition($this->registry);
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        foreach ($taggedServices as $id => $tags) {
            $priority = $this->resolvePriority($tags);
            $definition->addMethodCall(
                $this->method,
                [new Reference($id), $priority]
            );
        }
    }

    /**
     * @param array $tags
     *
     * @return int
     */
    protected function resolvePriority(array $tags)
    {
        foreach ($tags as $tag) {
            if (array_key_exists('priority', $tag)) {
                return $tag['priority'];
            }
        }

        return 0;
    }
}
