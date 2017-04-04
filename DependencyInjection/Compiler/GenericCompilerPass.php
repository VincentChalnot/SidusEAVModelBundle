<?php

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
     * FamilyCompilerPass constructor.
     *
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
     * Inject tagged families into configuration handler
     *
     * @param ContainerBuilder $container
     *
     * @api
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
            $definition->addMethodCall(
                $this->method,
                [new Reference($id)]
            );
        }
    }
}
