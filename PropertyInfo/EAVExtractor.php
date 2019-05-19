<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\PropertyInfo;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use function is_a;
use Sidus\EAVModelBundle\Annotation\Family as FamilyAnnotation;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extracts data using Doctrine ORM and ODM metadata.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVExtractor extends DoctrineExtractor implements PropertyAccessExtractorInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var AnnotationReader */
    protected $annotationReader;

    /** @var string */
    protected $dataClass;

    /**
     * @param ManagerRegistry  $doctrine
     * @param FamilyRegistry   $familyRegistry
     * @param AnnotationReader $annotationReader
     * @param string           $dataClass
     */
    public function __construct(
        ManagerRegistry $doctrine,
        FamilyRegistry $familyRegistry,
        AnnotationReader $annotationReader,
        string $dataClass
    ) {
        $this->doctrine = $doctrine;
        $this->familyRegistry = $familyRegistry;
        $this->annotationReader = $annotationReader;
        $this->dataClass = $dataClass;
        $entityManager = $doctrine->getManagerForClass($dataClass);
        if (!$entityManager) {
            throw new \UnexpectedValueException("No manager found for class {$dataClass}");
        }
        parent::__construct($entityManager->getMetadataFactory());
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    public function getProperties($class, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }

        $properties = parent::getProperties($class, $context);
        $family = $this->getFamily($class);
        if ($family) {
            foreach ($family->getAttributes() as $attribute) {
                if ($attribute->getOption('expose', true)) {
                    $properties[] = $attribute->getCode();
                }
            }
        }

        return $properties;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    public function getTypes($class, $property, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }

        $types = parent::getTypes($class, $property, $context);
        if (null !== $types) {
            return $types;
        }
        $family = $this->getFamily($class);
        if (!$family || !$family->hasAttribute($property)) {
            return null;
        }
        $attribute = $family->getAttribute($property);

        $types = parent::getTypes($family->getValueClass(), $attribute->getType()->getDatabaseType());
        foreach ($types as $key => $type) {
            if ($type instanceof Type && $attribute->isRequired()) {
                $newType = new Type(
                    $type->getBuiltinType(),
                    false, // Replace nullable for required attributes
                    $type->getClassName(),
                    $type->isCollection(),
                    $type->getCollectionKeyType(),
                    $type->getCollectionValueType()
                );
                $types[$key] = $newType;
            }
        }

        return $types;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \ReflectionException
     */
    public function isReadable($class, $property, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }
        $family = $this->getFamily($class);
        if (!$family || !$family->hasAttribute($property)) {
            return null;
        }
        $attribute = $family->getAttribute($property);

        return $attribute->getOption('readable', true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \ReflectionException
     */
    public function isWritable($class, $property, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }
        if ('id' === $property) {
            return false;
        }
        $family = $this->getFamily($class);
        if (!$family || !$family->hasAttribute($property)) {
            return null;
        }
        $attribute = $family->getAttribute($property);

        return $attribute->getOption('writable', true);
    }

    /**
     * @param $class
     *
     * @throws \ReflectionException
     *
     * @return FamilyInterface|null
     */
    protected function getFamily($class)
    {
        $annotation = $this->annotationReader->getClassAnnotation(
            new \ReflectionClass($class),
            FamilyAnnotation::class
        );
        if ($annotation instanceof FamilyAnnotation) {
            return $this->familyRegistry->getFamily($annotation->familyCode);
        }

        return null;
    }
}
