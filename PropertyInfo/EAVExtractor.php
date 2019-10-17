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

use Doctrine\Common\Collections\Collection;
use ReflectionException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use UnexpectedValueException;
use function array_key_exists;
use Doctrine\Common\Persistence\ManagerRegistry;
use function count;
use function in_array;
use function is_a;
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

    /** @var string */
    protected $dataClass;

    /**
     * @param ManagerRegistry $doctrine
     * @param FamilyRegistry  $familyRegistry
     * @param string          $dataClass
     */
    public function __construct(
        ManagerRegistry $doctrine,
        FamilyRegistry $familyRegistry,
        string $dataClass
    ) {
        $this->doctrine = $doctrine;
        $this->familyRegistry = $familyRegistry;
        $this->dataClass = $dataClass;
        $entityManager = $doctrine->getManagerForClass($dataClass);
        if (!$entityManager) {
            throw new UnexpectedValueException("No manager found for class {$dataClass}");
        }
        parent::__construct($entityManager->getMetadataFactory());
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function getProperties($class, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }

        $properties = parent::getProperties($class, $context);
        $family = $this->getFamily($class, $context);
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
     * @throws ReflectionException
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
        $family = $this->getFamily($class, $context);
        if (!$family || !$family->hasAttribute($property)) {
            return null;
        }
        $attribute = $family->getAttribute($property);

        $types = parent::getTypes($family->getValueClass(), $attribute->getType()->getDatabaseType());
        foreach ($types as $key => $type) {
            if (!$type instanceof Type) {
                throw new UnexpectedValueException('Unexpected type list');
            }
            if (is_a($type->getClassName(), DataInterface::class, true)) {
                $allowedFamilies = $attribute->getOption('allowed_families', []);
                if (count($allowedFamilies) > 0) {
                    return $this->handleDataRelation($attribute, $allowedFamilies);
                }
            }

            $type = new Type(
                $type->getBuiltinType(),
                !$attribute->isRequired(), // Replace nullable for required attributes
                $type->getClassName(),
                $type->isCollection(),
                $type->getCollectionKeyType(),
                $type->getCollectionValueType()
            );
            // If not a relation to an other DataInterface, just resolve collection and required attributes properly
            if ($attribute->isCollection()) {
                $type = new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    Collection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    $type
                );
            }
            $types[$key] = $type;
        }

        return $types;
    }

    /**
     * {@inheritDoc}
     *
     * @throws ReflectionException
     */
    public function isReadable($class, $property, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }
        $family = $this->getFamily($class, $context);
        if (!$family || !$family->hasAttribute($property)) {
            return null;
        }
        $attribute = $family->getAttribute($property);

        return $attribute->getOption('readable', true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ReflectionException
     */
    public function isWritable($class, $property, array $context = [])
    {
        if (!is_a($class, DataInterface::class, true)) {
            return null;
        }
        if (in_array($property, ['id', 'updatedAt', 'updatedBy', 'createdAt', 'createdBy'], true)) {
            return false;
        }
        $family = $this->getFamily($class, $context);
        if (!$family || !$family->hasAttribute($property)) {
            return null;
        }
        $attribute = $family->getAttribute($property);

        return $attribute->getOption('writable', true);
    }

    /**
     * @param string $class
     * @param array  $context
     *
     * @return FamilyInterface
     */
    protected function getFamily($class, array $context = [])
    {
        if (array_key_exists('family', $context)) {
            return $this->familyRegistry->getFamily($context['family']);
        }

        return $this->familyRegistry->getFamilyByDataClass($class);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array              $allowedFamilies
     *
     * @return Type[]
     */
    protected function handleDataRelation(
        AttributeInterface $attribute,
        array $allowedFamilies
    ) {
        $types = [];
        foreach ($allowedFamilies as $allowedFamilyCode) {
            $allowedFamily = $this->familyRegistry->getFamily($allowedFamilyCode);
            $type = new Type(
                Type::BUILTIN_TYPE_OBJECT,
                !$attribute->isRequired(),
                $allowedFamily->getDataClass()
            );
            if ($attribute->isCollection()) {
                $type = new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    Collection::class,
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    $type
                );
            }
            $types[] = $type;
        }

        return $types;
    }
}
