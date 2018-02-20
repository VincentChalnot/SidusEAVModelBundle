<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Serializer\Normalizer;

use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Standard normalizer and denormalizer for attributes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeNormalizer extends AbstractGenericNormalizer
{
    /**
     * @param AttributeInterface $object
     * @param string             $format
     * @param array              $context
     *
     * @return string
     */
    protected function getReference($object, $format, array $context)
    {
        return $object->getCode();
    }

    /**
     * @param AttributeInterface $object
     * @param string             $format
     * @param array              $context
     *
     * @return array
     */
    protected function getShortReference($object, $format, array $context)
    {
        return [
            'code' => $object->getCode(),
            'type' => $object->getType()->getCode(),
            'collection' => $object->isCollection(),
        ];
    }

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
