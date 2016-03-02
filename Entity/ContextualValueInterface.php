<?php

namespace Sidus\EAVModelBundle\Entity;

interface ContextualValueInterface
{
    /**
     * @return array
     */
    public function getContext();

    /**
     * @param string $key
     * @return mixed
     * @throws \UnexpectedValueException
     */
    public function getContextValue($key);

    /**
     * @param string $key
     * @param mixed $value
     * @throws \UnexpectedValueException
     */
    public function setContextValue($key, $value);

    /**
     * Context constructor.
     * @param array $context
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
