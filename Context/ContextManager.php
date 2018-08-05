<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Context;

/**
 * Manager for setting, saving and getting the current context when using ContextualData & ContextualValue
 *
 * This base service does not use the session anymore for context storage
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextManager implements ContextManagerInterface
{
    /** @var array */
    protected $defaultContext;

    /** @var array */
    protected $context;

    /**
     * @param array $defaultContext
     */
    public function __construct(array $defaultContext)
    {
        $this->defaultContext = $defaultContext;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        if (null === $this->context) {
            return $this->getDefaultContext();
        }

        return $this->context;
    }

    /**
     * This method is exposed only for command-line applications
     *
     * @param array $context
     *
     * @internal Warning, this method will save the context without any checks on the values
     */
    public function setContext(array $context): void
    {
        $context = array_merge($this->getDefaultContext(), $context);
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getDefaultContext(): array
    {
        return $this->defaultContext;
    }
}
