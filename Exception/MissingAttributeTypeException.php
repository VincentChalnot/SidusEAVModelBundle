<?php

namespace Sidus\EAVModelBundle\Exception;

/**
 * Exception thrown when trying to fetch a missing attribute
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingAttributeTypeException extends \UnexpectedValueException implements EAVExceptionInterface
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No attribute type with code : {$code}");
    }
}
