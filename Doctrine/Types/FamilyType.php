<?php

namespace Sidus\EAVModelBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
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

        /** @var FamilyConfigurationHandler $familyConfigurationHandler */
        $familyConfigurationHandler = array_shift($listeners);

        return $familyConfigurationHandler->getFamily($value);
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
