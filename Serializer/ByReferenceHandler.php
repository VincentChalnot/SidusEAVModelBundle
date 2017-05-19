<?php

namespace Sidus\EAVModelBundle\Serializer;

use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Allowing serializer to behave differently based on the by_reference or by_short_reference context options
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
