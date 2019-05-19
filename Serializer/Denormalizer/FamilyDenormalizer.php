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

use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalize families to the real service
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyDenormalizer implements DenormalizerInterface
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /**
     * @param FamilyRegistry $familyRegistry
     */
    public function __construct(FamilyRegistry $familyRegistry)
    {
        $this->familyRegistry = $familyRegistry;
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed  $data    data to restore
     * @param string $class   the expected class to instantiate
     * @param string $format  format the given data was extracted from
     * @param array  $context options available to the denormalizer
     *
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     *
     * @return FamilyInterface
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if ($data instanceof FamilyInterface) {
            return $data;
        }
        if (\is_string($data)) {
            return $this->resolveFamily($data);
        }
        if (\is_array($data)) {
            foreach (['family', 'familyCode', 'family_code', 'code'] as $property) {
                if (array_key_exists($property, $data)) {
                    return $this->resolveFamily($data[$property]);
                }
            }
        }

        throw new UnexpectedValueException('Unknown data format');
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
        return is_a($type, FamilyInterface::class, true);
    }

    /**
     * @param string $familyCode
     *
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     *
     * @return FamilyInterface
     */
    protected function resolveFamily($familyCode)
    {
        try {
            return $this->familyRegistry->getFamily($familyCode);
        } catch (MissingFamilyException $e) {
            throw new UnexpectedValueException($e->getMessage(), 0, $e);
        }
    }
}
