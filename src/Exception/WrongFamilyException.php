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

namespace Sidus\EAVModelBundle\Exception;

use Sidus\EAVModelBundle\Data\DataInterface;
use Sidus\EAVModelBundle\Family\FamilyInterface;

/**
 * Exception thrown when data is passed to a family that doesn't match.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class WrongFamilyException extends \UnexpectedValueException implements EAVExceptionInterface
{
    public static function fromData(DataInterface $data, FamilyInterface $expectedFamily): self
    {
        return new self(sprintf(
            'Data belongs to family "%s" but was expected to belong to family "%s"',
            $data->getFamily()->getCode(),
            $expectedFamily->getCode()
        ));
    }

    /**
     * @param FamilyInterface[] $allowedFamilies
     */
    public static function fromAllowedFamilies(DataInterface $data, array $allowedFamilies): self
    {
        $allowedCodes = array_map(
            static fn(FamilyInterface $family): string => $family->getCode(),
            $allowedFamilies
        );

        return new self(sprintf(
            'Data belongs to family "%s" which is not in the allowed families: %s',
            $data->getFamily()->getCode(),
            implode(', ', $allowedCodes)
        ));
    }

    /**
     * Assert that a data belongs to the expected family.
     */
    public static function assertFamily(DataInterface $data, FamilyInterface $family): void
    {
        if ($data->getFamily()->getCode() !== $family->getCode()) {
            throw self::fromData($data, $family);
        }
    }
}
