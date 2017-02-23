<?php

namespace Sidus\EAVModelBundle\Entity;

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
     * @return mixed
     * @throws \UnexpectedValueException
     */
    public function getContextValue($key);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \UnexpectedValueException
     */
    public function setContextValue($key, $value);

    /**
     * Context constructor.
     *
     * @param array $context
     *
     * @throws \UnexpectedValueException
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
