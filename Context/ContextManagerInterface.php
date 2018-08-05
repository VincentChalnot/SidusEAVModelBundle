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
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextManagerInterface
{
    /**
     * @return array
     */
    public function getContext(): array;

    /**
     * This method is exposed only for command-line applications
     *
     * @param array $context
     *
     * @internal
     */
    public function setContext(array $context): void;

    /**
     * @return array
     */
    public function getDefaultContext(): array;
}
