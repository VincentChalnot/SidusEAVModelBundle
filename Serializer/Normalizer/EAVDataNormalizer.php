<?php

namespace Sidus\EAVModelBundle\Serializer\Normalizer;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Exception\EAVExceptionInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Standard normalizer for EAV Data
 */
class EAVDataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    const DEPTH_KEY = 'depth';
    const GROUPS = 'groups';

    /** @var ClassMetadataFactoryInterface */
    protected $classMetadataFactory;

    /** @var NameConverterInterface */
    protected $nameConverter;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var PropertyTypeExtractorInterface */
    protected $propertyTypeExtractor;

    /** @var array */
    protected $ignoredAttributes;

    /** @var array */
    protected $referenceAttributes;

    /**
     * @param ClassMetadataFactoryInterface|null  $classMetadataFactory
     * @param NameConverterInterface|null         $nameConverter
     * @param PropertyAccessorInterface|null      $propertyAccessor
     * @param PropertyTypeExtractorInterface|null $propertyTypeExtractor
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null
    ) {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->nameConverter = $nameConverter;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->propertyTypeExtractor = $propertyTypeExtractor;
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
        return $data instanceof DataInterface;
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @param DataInterface $object  object to normalize
     * @param string        $format  format the normalization result will be encoded as
     * @param array         $context Context options for the normalizer
     *
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\PropertyAccess\Exception\ExceptionInterface
     * @throws \Sidus\EAVModelBundle\Exception\EAVExceptionInterface
     * @throws \Sidus\EAVModelBundle\Exception\InvalidValueDataException
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!array_key_exists(self::DEPTH_KEY, $context)) {
            $context[self::DEPTH_KEY] = 0;
        }

        if (array_key_exists('by_short_reference', $context) ? $context['by_short_reference'] : false) {
            return $object->getIdentifier();
        }

        $data = [];

        foreach ($this->extractStandardAttributes($object, $format, $context) as $attribute) {
            $subContext = $context; // Copy context and force by reference
            $subContext['by_reference'] = true; // Keep in mind that the normalizer might not support it
            $attributeValue = $this->getAttributeValue($object, $attribute, $format, $subContext);
            $data = $this->updateData($data, $attribute, $attributeValue);
        }

        foreach ($this->extractEAVAttributes($object, $format, $context) as $attribute) {
            $attributeValue = $this->getEAVAttributeValue($object, $attribute, $format, $context);
            $data = $this->updateData($data, $attribute->getCode(), $attributeValue);
        }

        return $data;
    }

    /**
     * Set ignored attributes for normalization and denormalization.
     *
     * @param array $ignoredAttributes
     */
    public function setIgnoredAttributes(array $ignoredAttributes)
    {
        $this->ignoredAttributes = $ignoredAttributes;
    }

    /**
     * Set attributes used to normalize a data by reference
     *
     * @param array $referenceAttributes
     */
    public function setReferenceAttributes(array $referenceAttributes)
    {
        $this->referenceAttributes = $referenceAttributes;
    }

    /**
     * Sets an attribute and apply the name converter if necessary.
     *
     * @param array  $data
     * @param string $attribute
     * @param mixed  $attributeValue
     *
     * @return array
     */
    protected function updateData(array $data, $attribute, $attributeValue)
    {
        if ($this->nameConverter) {
            $attribute = $this->nameConverter->normalize($attribute);
        }

        $data[$attribute] = $attributeValue;

        return $data;
    }

    /**
     * @param DataInterface $object
     * @param string        $attribute
     * @param string        $format
     * @param array         $context
     *
     * @throws \Symfony\Component\PropertyAccess\Exception\ExceptionInterface
     *
     * @return mixed
     */
    protected function getAttributeValue(
        DataInterface $object,
        $attribute,
        $format = null,
        array $context = []
    ) {
        $rawValue = $this->propertyAccessor->getValue($object, $attribute);

        $subContext = array_merge(
            $context,
            [
                self::DEPTH_KEY => $context[self::DEPTH_KEY] + 1,
                'parent' => $object,
                'attribute' => $attribute,
            ]
        );

        return $this->normalizer->normalize($rawValue, $format, $subContext);
    }

    /**
     * @param DataInterface      $object
     * @param AttributeInterface $attribute
     * @param string             $format
     * @param array              $context
     *
     * @throws EAVExceptionInterface
     *
     * @return mixed
     */
    protected function getEAVAttributeValue(
        DataInterface $object,
        AttributeInterface $attribute,
        $format = null,
        array $context = []
    ) {
        $rawValue = $object->get($attribute->getCode());

        $options = $attribute->getOption('serializer', []);
        $shortReference = array_key_exists('by_short_reference', $options) ? $options['by_short_reference'] : false;

        $subContext = array_merge(
            $context,
            [
                self::DEPTH_KEY => $context[self::DEPTH_KEY] + 1,
                'parent' => $object,
                'attribute' => $attribute->getCode(),
                'eav_attribute' => $attribute,
                'by_reference' => $attribute->getType()->isRelation(),
                'by_short_reference' => $shortReference,
            ]
        );

        return $this->normalizer->normalize($rawValue, $format, $subContext);
    }

    /**
     * @param DataInterface $object
     * @param string        $format
     * @param array         $context
     *
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     *
     * @return array
     */
    protected function extractStandardAttributes(DataInterface $object, $format = null, array $context = [])
    {
        // If not using groups, detect manually
        $attributes = [];

        // methods
        $reflClass = new \ReflectionClass($object);
        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflMethod) {
            if ($reflMethod->getNumberOfRequiredParameters() !== 0 ||
                $reflMethod->isStatic() ||
                $reflMethod->isConstructor() ||
                $reflMethod->isDestructor()
            ) {
                continue;
            }

            $name = $reflMethod->name;
            $attributeName = null;

            if (0 === strpos($name, 'get') || 0 === strpos($name, 'has')) {
                // getters and hassers
                $attributeName = lcfirst(substr($name, 3));
            } elseif (strpos($name, 'is') === 0) {
                // issers
                $attributeName = lcfirst(substr($name, 2));
            }

            // Skipping eav attributes
            if ($object->getFamily()->hasAttribute($attributeName)) {
                continue;
            }

            if (null !== $attributeName && $this->isAllowedAttribute($object, $attributeName, $format, $context)) {
                $attributes[$attributeName] = true;
            }
        }

        return array_keys($attributes);
    }

    /**
     * @param DataInterface $object
     * @param string        $format
     * @param array         $context
     *
     * @return \Sidus\EAVModelBundle\Model\AttributeInterface[]
     */
    protected function extractEAVAttributes(DataInterface $object, $format = null, array $context = [])
    {
        $allowedAttributes = [];
        foreach ($object->getFamily()->getAttributes() as $attribute) {
            if ($this->isAllowedAttribute($object, $attribute->getCode(), $format, $context)) {
                $allowedAttributes[] = $attribute;
            }
        }

        return $allowedAttributes;
    }

    /**
     * Is this attribute allowed?
     *
     * @param DataInterface $object
     * @param string        $attribute
     * @param string|null   $format
     * @param array         $context
     *
     * @return bool
     */
    protected function isAllowedAttribute(
        DataInterface $object,
        $attribute,
        /** @noinspection PhpUnusedParameterInspection */
        $format = null,
        array $context = []
    ) {
        // Ignore attributes set as serializer: expose: false
        if ($object->getFamily()->hasAttribute($attribute)) {
            $eavAttribute = $object->getFamily()->getAttribute($attribute);
            $options = $eavAttribute->getOption('serializer', []);
            if (array_key_exists('expose', $options) && !$options['expose']) {
                return false;
            }
        }

        if (array_key_exists('by_reference', $context) && $context['by_reference']) {
            return in_array($attribute, $this->referenceAttributes, true);
        }

        return !in_array($attribute, $this->ignoredAttributes, true);
    }
}
