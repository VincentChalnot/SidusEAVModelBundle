<?php

namespace Sidus\EAVModelBundle\Exception;

/**
 * Exception thrown when trying to fetch a missing attribute
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingAttributeException extends \UnexpectedValueException
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No attribute with code : {$code}");
    }
}
