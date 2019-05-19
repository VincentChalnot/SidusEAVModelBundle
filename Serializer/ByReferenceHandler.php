<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        if (array_key_exists(static::BY_SHORT_REFERENCE_KEY, $context)) {
            return $context[static::BY_SHORT_REFERENCE_KEY];
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
        if (array_key_exists(static::BY_REFERENCE_KEY, $context)) {
            return $context[static::BY_REFERENCE_KEY];
        }

        return false;
    }
}
