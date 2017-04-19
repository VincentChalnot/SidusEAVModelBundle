<?php

namespace Sidus\EAVModelBundle\Serializer\Denormalizer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize EAV Data
 */
class EAVDataDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var Registry */
    protected $doctrine;

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
     * @param Registry                       $doctrine
     * @param array                          $ignoredAttributes
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $accessor = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        FamilyRegistry $familyRegistry,
        Registry $doctrine,
        array $ignoredAttributes
    ) {
        $this->familyRegistry = $familyRegistry;
        $this->doctrine = $doctrine;
        $this->nameConverter = $nameConverter;
        $this->accessor = $accessor ?: PropertyAccess::createPropertyAccessor();
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
     * @throws UnexpectedValueException
     *
     * @return DataInterface
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($data instanceof DataInterface) {
            return $data; // Just in case...
        }

        $family = $this->getFamily($data, $class, $context);
        $entity = $this->getEntity($family, $data);
        if (is_scalar($data)) {
            return $entity; // In case we are trying to resolve a simple reference
        }
        /** @var array $data At this point we know for sure data is a \ArrayAccess or a PHP array */
        foreach ($data as $attributeCode => $value) {
            if ($this->nameConverter) {
                $attributeCode = $this->nameConverter->denormalize($attributeCode);
            }
            if (!$this->isAllowedAttributes($attributeCode)) {
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
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     */
    protected function handleAttributeValue(
        FamilyInterface $family,
        $attributeCode,
        DataInterface $entity,
        $normalizedValue,
        $format,
        array $context
    ) {
        if (null === $normalizedValue) {
            $value = null;
        } elseif ($family->hasAttribute($attributeCode)) {
            $attribute = $family->getAttribute($attributeCode);
            if ($attribute->isCollection()) {
                if (!is_array($normalizedValue) && !$normalizedValue instanceof \Traversable) {
                    throw new UnexpectedValueException(
                        "Given data should be an array of values for attribute {$attributeCode}"
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
            if ($attributeCode === 'label') {
                // Skip the "setValue" for a standard "label" attribute, due to conflict with the attributeAsLabel option
                return;
            }
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
     * @throws UnexpectedValueException
     *
     * @return \Sidus\EAVModelBundle\Model\FamilyInterface
     */
    protected function getFamily($data, $class, array $context)
    {
        if (is_array($data) || $data instanceof \ArrayAccess) {
            foreach (['familyCode', 'family_code', 'family'] as $property) {
                if (array_key_exists($property, $data)) {
                    return $this->denormalizer->denormalize($data[$property], FamilyInterface::class, $context);
                }
            }
        }

        // Check if family information is present in the context
        if (array_key_exists('family', $context)) {
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
     * @param array|\ArrayAccess $data
     * @param FamilyInterface    $family
     *
     * @return mixed
     */
    protected function resolveIdentifier($data, FamilyInterface $family)
    {
        if ($family->getAttributeAsIdentifier()) {
            $attributeCode = $family->getAttributeAsIdentifier()->getCode();
            if ($this->accessor->isReadable($data, $attributeCode)) {
                return $this->accessor->getValue($data, $attributeCode);
            }
        }

        return null;
    }

    /**
     * @param FamilyInterface $family
     * @param mixed           $data
     *
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     *
     * @return DataInterface
     */
    protected function getEntity(FamilyInterface $family, $data)
    {
        /** @var DataRepository $repository */
        $repository = $this->doctrine->getRepository($family->getDataClass());

        if ($family->isSingleton()) {
            try {
                return $repository->getInstance($family);
            } catch (\Exception $e) {
                throw new UnexpectedValueException("Unable to get singleton for family {$family->getCode()}", 0, $e);
            }
        }

        // In case we are trying to resolve a simple reference
        if (is_scalar($data)) {
            try {
                return $repository->findByIdentifier($family, $data, true);
            } catch (\Exception $e) {
                throw new UnexpectedValueException("Unable to resolve id/identifier {$data}", 0, $e);
            }
        }

        if (!is_array($data) && !$data instanceof \ArrayAccess) {
            throw new UnexpectedValueException('Unable to denormalize data from unknown format');
        }

        // If the id is set, don't even look for the identifier
        if (array_key_exists('id', $data)) {
            return $repository->find($data['id']);
        }

        // Try to resolve the identifier
        $reference = $this->resolveIdentifier($data, $family);

        if (null === $reference) {
            return $family->createData();
        }
        try {
            return $repository->findByIdentifier($family, $reference);
        } catch (\Exception $e) {
            throw new UnexpectedValueException("Unable to resolve identifier {$reference}", 0, $e);
        }
    }

    /**
     * @param string $attributeCode
     *
     * @return bool
     */
    protected function isAllowedAttributes($attributeCode)
    {
        return !in_array($attributeCode, $this->ignoredAttributes, true);
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param mixed              $value
     * @param string             $format
     * @param array              $context
     *
     * @return mixed
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     */
    protected function denormalizeEAVAttribute(
        FamilyInterface $family,
        AttributeInterface $attribute,
        $value,
        $format,
        array $context
    ) {
        $attributeType = $attribute->getType();
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $valueMetadata = $this->doctrine->getManager()->getClassMetadata($family->getValueClass());
        $storageField = $attributeType->getDatabaseType();
        if ($valueMetadata->hasAssociation($storageField)) {
            $targetClass = $valueMetadata->getAssociationTargetClass($attributeType->getDatabaseType());
            $context['relatedAttribute'] = $attribute; // Add attribute info
            $allowedFamilies = $attribute->getOption('allowed_families', []);
            if (1 === count($allowedFamilies)) {
                $context['family'] = array_pop($allowedFamilies);
            }

            return $this->denormalizeRelation($value, $targetClass, $format, $context);
        }

        if ($valueMetadata->hasField($storageField)) {
            $type = $valueMetadata->getTypeOfField($storageField);

            if ($type === 'datetime' || $type === 'date') {
                return $this->denormalizeRelation($value, \DateTime::class, $format, $context);
            }
            return $value;
        }

        throw new UnexpectedValueException("Unknown database type {$storageField} for family {$family->getCode()}");
    }

    /**
     * @param FamilyInterface $family
     * @param string          $attributeCode
     * @param mixed           $value
     * @param string          $format
     * @param array           $context
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
        // @todo handles standard serializer annotations ?

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $classMetadata = $this->doctrine->getManager()->getClassMetadata($family->getDataClass());
        if ($classMetadata->hasAssociation($attributeCode)) {
            $targetClass = $classMetadata->getAssociationTargetClass($attributeCode);

            return $this->denormalizeRelation($value, $targetClass, $format, $context);
        }

        if ($classMetadata->hasField($attributeCode)) {
            $type = $classMetadata->getTypeOfField($attributeCode);
            if ($type === 'datetime' || $type === 'date') {
                return $this->denormalizeRelation($value, \DateTime::class, $format, $context);
            }
        }

        return $value;
    }


    /**
     * @param string $value
     * @param string $targetClass
     * @param string $format
     * @param array  $context
     *
     * @return mixed
     */
    protected function denormalizeRelation($value, $targetClass, $format, array $context)
    {
        // @todo Handles DataInterface properly ?
//        dump($targetClass, $context, $value);
//        exit;

        return $this->denormalize($value, $targetClass, $format, $context);
    }
}
