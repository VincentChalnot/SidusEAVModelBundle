<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * @todo:
 *      Check if there are better places to inject those global variables
 *      Move getter and setters logic to attribute types
 *      events & behaviors
 *      move __debugInfo content into family
 *      attribute type parsing [] + family codes
 *      keep "values" attribute configuration entry?
 *      forbidden family codes when existing attribute type code collision
 *
 *
 *
 * Stores the global configuration variables
 */
class EAVConfiguration
{
    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $valueClass;

    /** @var array */
    protected $defaultContext;
}
