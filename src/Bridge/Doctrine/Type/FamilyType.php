<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Bridge\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Sidus\EAVModelBundle\Family\FamilyInterface;
use Sidus\EAVModelBundle\Family\FamilyRegistry;

/**
 * Custom Doctrine type for storing and retrieving Family entities.
 *
 * This type stores the family code as a string in the database and
 * converts it to/from the actual Family object using the FamilyRegistry.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyType extends Type
{
    public const TYPE_NAME = 'sidus_family';

    private static ?FamilyRegistry $familyRegistry = null;

    /**
     * Sets the family registry instance (called from DI).
     */
    public static function setFamilyRegistry(FamilyRegistry $familyRegistry): void
    {
        self::$familyRegistry = $familyRegistry;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = 255;
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?FamilyInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (self::$familyRegistry === null) {
            throw new \LogicException(
                'FamilyRegistry has not been injected into FamilyType. ' .
                'Make sure the Doctrine event listener is properly configured.'
            );
        }

        return self::$familyRegistry->getFamily($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof FamilyInterface) {
            return $value->getCode();
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::TYPE_NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
