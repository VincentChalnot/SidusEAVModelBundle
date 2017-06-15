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

namespace Sidus\EAVModelBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;

/**
 * Doctrine type extension to link families directly in entities
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyType extends StringType
{
    const FAMILY = 'sidus_family';

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return null|FamilyInterface
     * @throws MissingFamilyException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }
        $listeners = $platform->getEventManager()->getListeners('sidus_family_configuration');

        /** @var FamilyRegistry $familyRegistry */
        $familyRegistry = array_shift($listeners);

        return $familyRegistry->getFamily($value);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value instanceof FamilyInterface) {
            throw new \UnexpectedValueException('Value must implements FamilyInterface');
        }

        return $value->getCode();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::FAMILY; // modify to match your constant name
    }
}
