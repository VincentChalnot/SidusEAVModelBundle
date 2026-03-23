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

namespace Sidus\EAVModelBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating EAV data entities.
 *
 * @Annotation
 * @Target({"CLASS"})
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Data extends Constraint
{
    public string $message = 'The data is invalid.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
