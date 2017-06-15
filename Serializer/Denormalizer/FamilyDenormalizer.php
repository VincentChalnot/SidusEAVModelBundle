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
        if (is_string($data)) {
            return $this->resolveFamily($data);
        }
        if (is_array($data)) {
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
