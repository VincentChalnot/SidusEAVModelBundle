<?php

namespace Sidus\EAVModelBundle\Serializer\Normalizer\Flat;

use Sidus\EAVModelBundle\Serializer\ByReferenceHandler;
use Sidus\EAVModelBundle\Serializer\MaxDepthHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Normalizer variant for flat formats (csv, tsv...)
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
