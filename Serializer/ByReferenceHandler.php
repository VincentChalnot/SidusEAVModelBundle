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

namespace Sidus\EAVModelBundle\Serializer;

/**
 * Allowing serializer to behave differently based on the by_reference or by_short_reference context options
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ByReferenceHandler
{
    const BY_SHORT_REFERENCE_KEY = 'by_short_reference';
    const BY_REFERENCE_KEY = 'by_reference';

    /**
     * @param array $context
     *
     * @return bool
     */
    public function isByShortReference(array $context)
    {
        if (array_key_exists(self::BY_SHORT_REFERENCE_KEY, $context)) {
            return $context[self::BY_SHORT_REFERENCE_KEY];
        }

        return false;
    }

    /**
     * @param array $context
     *
     * @return bool
     */
    public function isByReference(array $context)
    {
        if (array_key_exists(self::BY_REFERENCE_KEY, $context)) {
            return $context[self::BY_REFERENCE_KEY];
        }

        return false;
    }
}
