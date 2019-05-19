<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Serializer\Normalizer;

use Sidus\EAVModelBundle\Serializer\ByReferenceHandler;
use Sidus\EAVModelBundle\Serializer\MaxDepthHandler;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Standard normalizer and denormalizer for basic objects with max depth and by reference handler
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractGenericNormalizer extends ObjectNormalizer
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

        if ($this->byReferenceHandler->isByShortReference($context)) {
            return $this->getShortReference($object, $format, $context);
        }

        if ($this->byReferenceHandler->isByReference($context)) {
            return $this->getReference($object, $format, $context);
        }

        try {
            return parent::normalize($object, $format, $context);
        } catch (CircularReferenceException $e) {
            return $this->getShortReference($object, $format, $context);
        }
    }

    /**
     * @param mixed  $object
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    abstract protected function getReference($object, $format, array $context);

    /**
     * @param mixed  $object
     * @param string $format
     * @param array  $context
     *
     * @return array
     */
    abstract protected function getShortReference($object, $format, array $context);
}
