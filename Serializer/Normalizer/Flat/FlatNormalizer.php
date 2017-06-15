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

namespace Sidus\EAVModelBundle\Serializer\Normalizer\Flat;

use Sidus\EAVModelBundle\Serializer\ByReferenceHandler;
use Sidus\EAVModelBundle\Serializer\MaxDepthHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Normalizer variant for flat formats (csv, tsv...)
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FlatNormalizer implements NormalizerInterface, NormalizerAwareInterface, SerializerAwareInterface
{
    /** @var NormalizerInterface */
    protected $baseNormalizer;

    /** @var array */
    protected $supportedFormats = [];

    /**
     * @param NormalizerInterface $baseNormalizer
     * @param array               $supportedFormats
     */
    public function __construct(NormalizerInterface $baseNormalizer, array $supportedFormats)
    {
        $this->baseNormalizer = $baseNormalizer;
        $this->supportedFormats = $supportedFormats;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (array_key_exists(MaxDepthHandler::DEPTH_KEY, $context) && $context[MaxDepthHandler::DEPTH_KEY] > 0) {
            $context[ByReferenceHandler::BY_SHORT_REFERENCE_KEY] = true;
        }

        return $this->baseNormalizer->normalize($object, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->baseNormalizer->supportsNormalization($data, $format) && in_array(
            $format,
            $this->supportedFormats,
            true
        );
    }

    /**
     * @param NormalizerInterface $normalizer
     */
    public function setNormalizer(NormalizerInterface $normalizer)
    {
        if ($this->baseNormalizer instanceof NormalizerAwareInterface) {
            $this->baseNormalizer->setNormalizer($normalizer);
        }
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if ($this->baseNormalizer instanceof SerializerAwareInterface) {
            $this->baseNormalizer->setSerializer($serializer);
        }
    }
}
