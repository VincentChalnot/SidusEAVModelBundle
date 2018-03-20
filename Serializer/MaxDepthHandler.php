<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        if (!array_key_exists(static::DEPTH_KEY, $context)) {
            $context[static::DEPTH_KEY] = 0;
        }
        $context[static::DEPTH_KEY]++;
        if (!array_key_exists(static::MAX_DEPTH_KEY, $context)) {
            $context[static::MAX_DEPTH_KEY] = $defaultMaxDepth;
        }
        if ($context[static::DEPTH_KEY] > $context[static::MAX_DEPTH_KEY]) {
            throw new RuntimeException('Max depth reached');
        }
    }

    /**
     * @param array $context
     */
    public function incrementDepth(array &$context)
    {
        $context[static::DEPTH_KEY]++;
    }
}
