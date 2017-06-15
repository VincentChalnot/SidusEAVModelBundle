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

namespace Sidus\EAVModelBundle\Serializer\Normalizer;

use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Standard normalizer and denormalizer for families
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyNormalizer extends AbstractGenericNormalizer
{
    /**
     * @param FamilyInterface $object
     * @param string          $format
     * @param array           $context
     *
     * @return string
     */
    protected function getReference($object, $format, array $context)
    {
        return $object->getCode();
    }

    /**
     * @param FamilyInterface $object
     * @param string          $format
     * @param array           $context
     *
     * @return array
     */
    protected function getShortReference($object, $format, array $context)
    {
        return [
            'code' => $object->getCode(),
        ];
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
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed  $data   Data to normalize
     * @param string $format The format being (de-)serialized from or into
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof FamilyInterface;
    }
}
