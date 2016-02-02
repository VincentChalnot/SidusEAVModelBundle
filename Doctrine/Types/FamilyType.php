<?php

namespace Sidus\EAVModelBundle\Doctrine\Types;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Model\FamilyInterface;

class FamilyType extends StringType
{
    const FAMILY = 'sidus_family';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }
        $listeners = $platform->getEventManager()->getListeners('sidus_family_configuration');

        /** @var FamilyConfigurationHandler $listener */
        $familyConfigurationHandler = array_shift($listeners);

        return $familyConfigurationHandler->getFamily($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value instanceof FamilyInterface) {
            throw new \UnexpectedValueException('Value must implements FamilyInterface');
        }
        return $value->getCode();
    }

    public function getName()
    {
        return self::FAMILY; // modify to match your constant name
    }
}
