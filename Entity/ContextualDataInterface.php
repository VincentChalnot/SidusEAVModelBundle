<?php

namespace Sidus\EAVModelBundle\Entity;

/**
 * All data must implements this class that defines how context information is handled
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualDataInterface extends DataInterface
{
    /**
     * @return array
     */
    public function getCurrentContext();

    /**
     * @param array $currentContext
     */
    public function setCurrentContext(array $currentContext = []);
}
