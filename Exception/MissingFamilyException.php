<?php

namespace Sidus\EAVModelBundle\Exception;

/**
 * Exception launched when trying to fetch a missing family
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingFamilyException extends \UnexpectedValueException implements EAVExceptionInterface
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No family with code : {$code}");
    }
}
