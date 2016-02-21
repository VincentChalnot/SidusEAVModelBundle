<?php

namespace Sidus\EAVModelBundle\Entity;

interface ContextInterface
{
    /**
     * @param $key
     * @return mixed
     */
    public function getContextValue($key);

    /**
     * @param Value $value
     */
    public function setValue($value);
}
