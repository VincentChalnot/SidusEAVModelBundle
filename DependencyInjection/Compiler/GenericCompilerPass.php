<?php

namespace Sidus\EAVModelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class GenericCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $configurationHandler;

    /** @var string */
    protected $tag;

    /** @var string */
    protected $method;

    /**
     * FamilyCompilerPass constructor.
     * @param string $configurationHandler
     * @param string $tag
     * @param string $method
     */
    public function __construct($configurationHandler, $tag, $method)
    {
        $this->configurationHandler = $configurationHandler;
        $this->tag = $tag;
        $this->method = $method;
    }


    /**
     * Inject tagged families into configuration handler
     *
     * @param ContainerBuilder $container
     * @api
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has($this->configurationHandler)) {
            return;
        }

        $definition = $container->findDefinition($this->configurationHandler);
        $taggedServices = $container->findTaggedServiceIds($this->tag);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                $this->method,
                [new Reference($id)]
            );
        }
    }
}
