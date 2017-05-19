<?php

namespace Sidus\EAVModelBundle\Serializer;

use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Handling depth & max depth context in serialization
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
