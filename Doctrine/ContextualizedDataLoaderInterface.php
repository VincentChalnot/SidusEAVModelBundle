<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Doctrine;

/**
 * Allows you to inject a custom context into loaded entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualizedDataLoaderInterface extends DataLoaderInterface
{
    /**
     * @param array $context
     */
    public function setCurrentContext(array $context);
}
