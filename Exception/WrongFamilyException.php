<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
        self::assertFamilies($data, [$familyCode]);
    }

    /**
     * @param DataInterface $data
     * @param array        $familyCodes
     *
     * @throws WrongFamilyException
     */
    public static function assertFamilies(DataInterface $data, array $familyCodes)
    {
        if (in_array($data->getFamilyCode(), $familyCodes, true)) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $function = $backtrace[1]['class'].'::'.$backtrace[1]['function'];
        $families = implode(', ', $familyCodes);
        $m = "Argument 1 passed to {$function} must be of one of the following families: {$families}, '{$data->getFamilyCode()}' given";

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
