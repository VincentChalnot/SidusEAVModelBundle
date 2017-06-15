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

namespace Sidus\EAVModelBundle\Serializer\Denormalizer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Serializer\EntityProvider;
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
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVDataDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var Registry */
    protected $doctrine;

    /** @var EntityProvider */
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
     * @param Registry                       $doctrine
     * @param EntityProvider                 $entityProvider
     * @param array                          $ignoredAttributes
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $accessor = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        FamilyRegistry $familyRegistry,
        Registry $doctrine,
        EntityProvider $entityProvider,
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
     * @throws UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
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
        $entity = $this->entityProvider->getEntity($family, $data, $this->nameConverter);
        if (is_scalar($data)) {
            return $entity; // In case we are trying to resolve a simple reference
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
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\MissingAttributeException
     * @throws \InvalidArgumentException
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
                if (array_key_exists($property, $data) && $data[$property]) {
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
     * @param FamilyInterface $family
     * @param string          $attributeCode
     *
     * @return bool
     */
    protected function isAllowedAttributes(FamilyInterface $family, $attributeCode)
    {
        if ($attributeCode === 'label' && $family->hasAttribute('label')) {
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
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
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
        $attributeType = $attribute->getType();
        /** @var ClassMetadataInfo $valueMetadata */
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
     * @throws \InvalidArgumentException
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
        // @T0D0 handles standard serializer annotations ?
        /** @var ClassMetadataInfo $classMetadata */
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
        return $this->denormalizer->denormalize($value, $targetClass, $format, $context);
    }
}
