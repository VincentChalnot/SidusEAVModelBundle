<?php

namespace Sidus\EAVModelBundle\Exception;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;

/**
 * Allow the developer to add extra checks concerning the family of a data entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class WrongFamilyException extends ContextErrorException
{
    /**
     * @param DataInterface $data
     * @param string        $familyCode
     *
     * @throws WrongFamilyException
     */
    public static function assertFamily(DataInterface $data, $familyCode)
    {
        if ($data->getFamilyCode() === $familyCode) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $function = $backtrace[1]['class'].'::'.$backtrace[1]['function'];
        $m = "Argument 1 passed to {$function} must be of family {$familyCode}, {$data->getFamilyCode()} given";

        throw new self(
            'WrongFamilyException: '.$m, // message
            0, // code
            E_RECOVERABLE_ERROR, // severity
            $backtrace[0]['file'], // filename
            $backtrace[0]['line'], // line number
            [] // context
        );
    }
}
