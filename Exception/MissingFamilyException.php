<?php

namespace Sidus\EAVModelBundle\Exception;

class MissingFamilyException extends \UnexpectedValueException
{

    /**
     * MissingFamilyException constructor.
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No family with code : {$code}");
    }
}