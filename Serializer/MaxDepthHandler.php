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

use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Handling depth & max depth context in serialization
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MaxDepthHandler
{
    const DEPTH_KEY = 'depth';
    const MAX_DEPTH_KEY = 'max_depth';

    /**
     * @param array $context
     * @param int   $defaultMaxDepth
     *
     * @throws \Symfony\Component\Serializer\Exception\RuntimeException
     */
    public function handleMaxDepth(array &$context, $defaultMaxDepth = 10)
    {
        if (!array_key_exists(self::DEPTH_KEY, $context)) {
            $context[self::DEPTH_KEY] = 0;
        }
        $context[self::DEPTH_KEY]++;
        if (!array_key_exists(self::MAX_DEPTH_KEY, $context)) {
            $context[self::MAX_DEPTH_KEY] = $defaultMaxDepth;
        }
        if ($context[self::DEPTH_KEY] > $context[self::MAX_DEPTH_KEY]) {
            throw new RuntimeException('Max depth reached');
        }
    }

    /**
     * @param array $context
     */
    public function incrementDepth(array &$context)
    {
        $context[self::DEPTH_KEY]++;
    }
}
