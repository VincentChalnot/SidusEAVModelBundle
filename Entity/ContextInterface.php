<?php

namespace Sidus\EAVModelBundle\Entity;

interface ContextInterface
{
    /**
     * @param array $context
     */
    public function __construct(array $context);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param Value $value
     */
    public function setValue($value);

    /**
     * @return array
     */
    public function getAllowedKeys();

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);
}
