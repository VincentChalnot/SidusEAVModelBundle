<?php

namespace Sidus\EAVModelBundle\Serializer\Normalizer;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Serializer\ByReferenceHandler;
use Sidus\EAVModelBundle\Serializer\MaxDepthHandler;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Standard normalizer and denormalizer for attributes
 */
class AttributeNormalizer extends ObjectNormalizer
{
    /** @var MaxDepthHandler */
    protected $maxDepthHandler;

    /** @var ByReferenceHandler */
    protected $byReferenceHandler;

    /**
     * @param ClassMetadataFactoryInterface|null  $classMetadataFactory
     * @param NameConverterInterface|null         $nameConverter
     * @param PropertyAccessorInterface|null      $propertyAccessor
     * @param PropertyTypeExtractorInterface|null $propertyTypeExtractor
     * @param MaxDepthHandler                     $maxDepthHandler
     * @param ByReferenceHandler                  $byReferenceHandler
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        MaxDepthHandler $maxDepthHandler,
        ByReferenceHandler $byReferenceHandler
    ) {
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor);
        $this->maxDepthHandler = $maxDepthHandler;
        $this->byReferenceHandler = $byReferenceHandler;
    }

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Serializer\Exception\RuntimeException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $this->maxDepthHandler->handleMaxDepth($context);

        /** @var AttributeInterface $object */
        if ($this->byReferenceHandler->isByShortReference($context)) {
            return $object->getCode();
        }

        if ($this->byReferenceHandler->isByReference($context)) {
            return [
                'code' => $object->getCode(),
                'type' => $object->getType()->getCode(),
                'collection' => $object->isCollection(),
            ];
        }

        try {
            return parent::normalize($object, $format, $context);
        } catch (CircularReferenceException $e) {
            return $object->getCode();
        }
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
