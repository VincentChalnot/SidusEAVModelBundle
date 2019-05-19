<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Entity;

use Sidus\EAVModelBundle\Exception\ContextException;

/**
 * All values must implements this class that defines how context information is handled
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualValueInterface extends ValueInterface
{
    /**
     * @return array
     */
    public function getContext();

    /**
     * @param string $key
     *
     * @throws ContextException
     *
     * @return mixed
     */
    public function getContextValue($key);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws ContextException
     */
    public function setContextValue($key, $value);

    /**
     * @param array $context
     *
     * @throws ContextException
     */
    public function setContext(array $context);

    /**
     * Clean all contextual keys
     */
    public function clearContext();

    /**
     * @return array
     */
    public static function getContextKeys();

    /**
     * @param array $context
     *
     * @throws ContextException
     */
    public static function checkContext(array $context);
}
