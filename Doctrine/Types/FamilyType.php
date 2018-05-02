<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @throws MissingFamilyException
     *
     * @return null|FamilyInterface
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
     * @throws \UnexpectedValueException
     *
     * @return string
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
        return static::FAMILY; // modify to match your constant name
    }
}
