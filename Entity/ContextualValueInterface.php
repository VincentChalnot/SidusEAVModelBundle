<?php

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
     * Context constructor.
     *
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
    public function getContextKeys();
}
