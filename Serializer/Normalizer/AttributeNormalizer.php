<?php

namespace Sidus\EAVModelBundle\Serializer\Normalizer;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Standard normalizer and denormalizer for attributes
 */
class AttributeNormalizer extends ObjectNormalizer
{
    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     *
     * @param mixed  $data   Data to denormalize from
     * @param string $type   The class to which the data should be denormalized
     * @param string $format The format being deserialized from
     *
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, AttributeInterface::class, true);
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed  $data   Data to normalize
     * @param string $format The format being (de-)serialized from or into
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof AttributeInterface;
    }
}
