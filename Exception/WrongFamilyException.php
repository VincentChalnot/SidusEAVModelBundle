<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Exception;

use Sidus\EAVModelBundle\Entity\DataInterface;

/**
 * Allow the developer to add extra checks concerning the family of a data entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class WrongFamilyException extends \ErrorException implements EAVExceptionInterface
{
    /**
     * @param DataInterface $data
     * @param string        $familyCode
     *
     * @throws WrongFamilyException
     */
    public static function assertFamily(DataInterface $data, $familyCode)
    {
        static::assertFamilies($data, [$familyCode]);
    }

    /**
     * @param DataInterface $data
     * @param array         $familyCodes
     *
     * @throws WrongFamilyException
     */
    public static function assertFamilies(DataInterface $data, array $familyCodes)
    {
        if (\in_array($data->getFamilyCode(), $familyCodes, true)) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $function = $backtrace[1]['class'].'::'.$backtrace[1]['function'];
        $families = implode(', ', $familyCodes);
        $m = "Argument 1 passed to {$function} must be of one of the following families: {$families}, '{$data->getFamilyCode()}' given";

        throw new self(
            "WrongFamilyException: {$m}", // message
            0, // code
            E_RECOVERABLE_ERROR, // severity
            $backtrace[0]['file'], // filename
            $backtrace[0]['line'] // line number
        );
    }
}
