<?php

namespace Sidus\EAVModelBundle\Doctrine;

/**
 * Any class that holds DQL for the Query Builder must implements this interface
 */
interface DQLHandlerInterface
{
    /**
     * @return string
     */
    public function getDQL();

    /**
     * @return array
     */
    public function getParameters();
}
