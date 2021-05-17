<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Serializer\Denormalizer;

use ArrayAccess;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Sidus\EAVModelBundle\Entity\ContextualDataInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Exception\MissingAttributeException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Serializer\EntityProviderInterface;
use Sidus\EAVModelBundle\Serializer\Normalizer\EAVDataNormalizer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Traversable;
use function count;
use function in_array;
use function is_array;

/**
 * Denormalize EAV Data
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVDataDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;

    public const ORIGINAL_DATA = 'original_data';

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityProviderInterface */
    protected $entityProvider;

    /** @var NameConverterInterface */
    protected $nameConverter;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /** @var array */
    protected $ignoredAttributes;

    /** @var DenormalizerInterface */
    protected $denormalizer;

    /**
     * @param ClassMetadataFactoryInterface  $classMetadataFactory
     * @param NameConverterInterface         $nameConverter
     * @param PropertyAccessorInterface      $accessor
     * @param PropertyTypeExtractorInterface $propertyTypeExtractor
     * @param FamilyRegistry                 $familyRegistry
     * @param ManagerRegistry                $doctrine
     * @param EntityProviderInterface        $entityProvider
     * @param array                          $ignoredAttributes
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $accessor = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        FamilyRegistry $familyRegistry,
        ManagerRegistry $doctrine,
        EntityProviderInterface $entityProvider,
        array $ignoredAttributes
    ) {
        $this->nameConverter = $nameConverter;
        $this->accessor = $accessor ?: PropertyAccess::createPropertyAccessor();
        $this->familyRegistry = $familyRegistry;
        $this->doctrine = $doctrine;
        $this->entityProvider = $entityProvider;
        $this->ignoredAttributes = $ignoredAttributes;
    }

    /**
     * Sets the owning Denormalizer object.
     *
     * @param DenormalizerInterface $denormalizer
     */
    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed  $data    data to restore
     * @param string $class   the expected class to instantiate
     * @param string $format  format the given data was extracted from
     * @param array  $context options available to the denormalizer
     *
     * @throws InvalidArgumentException
     * @throws MissingAttributeException
     * @throws SerializerExceptionInterface
     *
     * @return DataInterface
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($data instanceof DataInterface) {
            return $data; // Just in case...
        }
        if (empty($data)) {
            return null; // No need to do anything if the data is empty
        }

        $family = $this->getFamily($data, $class, $context);
        unset($context['family'], $context['family_code'], $context['familyCode']); // Removing family info from context

        $entity = $this->extractObjectToPopulate(DataInterface::class, $context);
        if (!$entity) {
            $entity = $this->entityProvider->getEntity($family, $data, $this->nameConverter);
        }

        if (!$entity instanceof DataInterface) {
            throw new UnexpectedValueException(
                'EntityProvider::getEntity MUST ALWAYS return a DataInterface, check your custom code'
            );
        }

        if ($entity instanceof ContextualDataInterface
            && isset($context['context'])
            && is_array($context['context'])
        ) {
            $entity->setCurrentContext($context['context']);
        }
        if (is_scalar($data)) {
            return $entity; // In case we are trying to resolve a simple reference
        }

        if ($entity instanceof ContextualDataInterface
            && isset($data['currentContext'])
            && is_array($data['currentContext'])
        ) {
            $entity->setCurrentContext($data['currentContext']);
        }

        /** @var array $data At this point we know for sure data is a \ArrayAccess or a PHP array */
        foreach ($data as $attributeCode => $value) {
            if ($this->nameConverter) {
                $attributeCode = $this->nameConverter->denormalize($attributeCode);
            }
            if (!$this->isAllowedAttributes($family, $attributeCode)) {
                continue;
            }
            $this->handleAttributeValue($family, $attributeCode, $entity, $value, $format, $context);
        }

        return $entity;
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
        return is_a($type, DataInterface::class, true);
    }

    /**
     * @param FamilyInterface $family
     * @param string          $attributeCode
     * @param DataInterface   $entity
     * @param mixed           $normalizedValue
     * @param string          $format
     * @param array           $context
     *
     * @throws SerializerExceptionInterface
     * @throws MissingAttributeException
     * @throws InvalidArgumentException
     */
    protected function handleAttributeValue(
        FamilyInterface $family,
        $attributeCode,
        DataInterface $entity,
        $normalizedValue,
        $format,
        array $context
    ) {
        $context[static::ORIGINAL_DATA] = $entity;
        if (null === $normalizedValue) {
            $value = null;
        } elseif ($family->hasAttribute($attributeCode)) {
            $attribute = $family->getAttribute($attributeCode);
            if ($attribute->isCollection()) {
                if (!is_array($normalizedValue) && !$normalizedValue instanceof Traversable) {
                    throw new UnexpectedValueException(
                        "Given data should be an array of values for attribute '{$attributeCode}'"
                    );
                }
                $value = new ArrayCollection();
                /** @var array $normalizedValue */
                foreach ($normalizedValue as $item) {
                    $value[] = $this->denormalizeEAVAttribute(
                        $family,
                        $attribute,
                        $item,
                        $format,
                        $context
                    );
                }
            } else {
                $value = $this->denormalizeEAVAttribute(
                    $family,
                    $attribute,
                    $normalizedValue,
                    $format,
                    $context
                );
            }
        } else {
            $value = $this->denormalizeAttribute(
                $family,
                $attributeCode,
                $normalizedValue,
                $format,
                $context
            );
        }

        $this->accessor->setValue($entity, $attributeCode, $value);
    }

    /**
     * @param mixed  $data
     * @param string $class
     * @param array  $context
     *
     * @throws SerializerExceptionInterface
     *
     * @return FamilyInterface
     */
    protected function getFamily($data, $class, array $context)
    {
        if (is_array($data) || $data instanceof ArrayAccess) {
            foreach (['familyCode', 'family_code', 'family'] as $property) {
                if (array_key_exists($property, $data) && $data[$property]) {
                    /** @noinspection PhpIncompatibleReturnTypeInspection */

                    return $this->denormalizer->denormalize($data[$property], FamilyInterface::class, $context);
                }
            }
        }

        // Check if family information is present in the context
        if (array_key_exists('family', $context)) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */

            return $this->denormalizer->denormalize($context['family'], FamilyInterface::class, $context);
        }

        // Last attempt: try to determine the family from the class
        $matchingFamilies = [];
        foreach ($this->familyRegistry->getFamilies() as $family) {
            if ($family->isInstantiable() && $family->getDataClass() === $class) {
                $matchingFamilies[] = $family;
            }
        }

        // If there is only ONE family exactly matching the data class, let's use this one
        if (1 === count($matchingFamilies)) {
            return array_pop($matchingFamilies);
        }

        throw new UnexpectedValueException("Unable to determine the Family for the class {$class}");
    }

    /**
     * @param FamilyInterface $family
     * @param string          $attributeCode
     *
     * @return bool
     */
    protected function isAllowedAttributes(FamilyInterface $family, $attributeCode)
    {
        if ('label' === $attributeCode && $family->hasAttribute('label')) {
            return true;
        }

        return !in_array($attributeCode, $this->ignoredAttributes, true);
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param mixed              $value
     * @param string             $format
     * @param array              $context
     *
     * @throws InvalidArgumentException
     * @throws SerializerExceptionInterface
     *
     * @return mixed
     */
    protected function denormalizeEAVAttribute(
        FamilyInterface $family,
        AttributeInterface $attribute,
        $value,
        $format,
        array $context
    ) {
        $entityManager = $this->doctrine->getManagerForClass($family->getValueClass());
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \UnexpectedValueException("Missing entity manager for class {$family->getValueClass()}");
        }
        $attributeType = $attribute->getType();
        $valueMetadata = $entityManager->getClassMetadata($family->getValueClass());
        $storageField = $attributeType->getDatabaseType();
        $attributeReference = "{$family->getCode()}.{$attribute->getCode()}";

        $options = $attribute->getOption(EAVDataNormalizer::SERIALIZER_OPTIONS, []);
        $additionalContext = [];
        if (array_key_exists(EAVDataNormalizer::CONTEXT_KEY, $options)) {
            $additionalContext = $options[EAVDataNormalizer::CONTEXT_KEY];
        }

        if ($valueMetadata->hasAssociation($storageField)) {
            $targetClass = $valueMetadata->getAssociationTargetClass($attributeType->getDatabaseType());
            $context['relatedAttribute'] = $attribute; // Add attribute info
            $allowedFamilies = $attribute->getOption('allowed_families', []);
            if (1 === count($allowedFamilies)) {
                $context['family'] = array_pop($allowedFamilies);
            }
            $context = array_merge($context, $additionalContext);
            if (array_key_exists('merge_with_existing', $options)
                && $options['merge_with_existing']
                && array_key_exists(static::ORIGINAL_DATA, $context)
            ) {
                $entity = $context[static::ORIGINAL_DATA];
                if ($entity instanceof DataInterface) {
                    // EAV Context is already set in entity
                    $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $entity->get($attribute->getCode());
                }
            }
            if ($valueMetadata->isCollectionValuedAssociation($storageField)) {
                $targetClass .= '[]';
            }

            return $this->denormalizeRelation($value, $targetClass, $format, $context, $attributeReference);
        }

        if ($valueMetadata->hasField($storageField)) {
            $type = $valueMetadata->getTypeOfField($storageField);

            if ('datetime' === $type || 'date' === $type) {
                $context = array_merge($context, $additionalContext);

                return $this->denormalizeRelation($value, DateTime::class, $format, $context, $attributeReference);
            }

            return $value;
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new UnexpectedValueException("Unknown database type {$storageField} for family {$family->getCode()}");
    }

    /**
     * @param FamilyInterface $family
     * @param string          $attributeCode
     * @param mixed           $value
     * @param string          $format
     * @param array           $context
     *
     * @throws InvalidArgumentException
     * @throws SerializerExceptionInterface
     *
     * @return mixed
     */
    protected function denormalizeAttribute(
        FamilyInterface $family,
        $attributeCode,
        $value,
        $format,
        array $context
    ) {
        $entityManager = $this->doctrine->getManagerForClass($family->getDataClass());
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \UnexpectedValueException("Missing entity manager for class {$family->getDataClass()}");
        }
        $attributeReference = "{$family->getDataClass()}:{$attributeCode}";
        // @T0D0 handles standard serializer annotations ?
        $classMetadata = $entityManager->getClassMetadata($family->getDataClass());
        if ($classMetadata->hasAssociation($attributeCode)) {
            $targetClass = $classMetadata->getAssociationTargetClass($attributeCode);
            if ($classMetadata->isCollectionValuedAssociation($attributeCode)) {
                $targetClass .= '[]';
            }

            return $this->denormalizeRelation($value, $targetClass, $format, $context, $attributeReference);
        }

        if ($classMetadata->hasField($attributeCode)) {
            $type = $classMetadata->getTypeOfField($attributeCode);
            if ('datetime' === $type || 'date' === $type) {
                return $this->denormalizeRelation($value, DateTime::class, $format, $context, $attributeReference);
            }
        }

        return $value;
    }


    /**
     * @param string      $value
     * @param string      $targetClass
     * @param string      $format
     * @param array       $context
     * @param string|null $attributeReference
     *
     * @return mixed
     */
    protected function denormalizeRelation(
        $value,
        $targetClass,
        $format,
        array $context,
        $attributeReference = null
    ) {
        if (null === $value || '' === $value) {
            return null;
        }
        try {
            return $this->denormalizer->denormalize($value, $targetClass, $format, $context);
        } catch (Exception $e) {
            if (!is_scalar($value)) {
                $value = '['.gettype($value).']';
            }
            $m = "Unable to denormalize value '{$value}'";
            if ($attributeReference) {
                $m .= " for attribute {$attributeReference}";
            }
            throw new NotNormalizableValueException($m, 0, $e);
        }
    }
}
