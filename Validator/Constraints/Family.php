<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Check if an object is of the proper family
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @Annotation
 */
class Family extends Constraint
{
    const INVALID_EAV_FAMILY_ERROR = 'e67e56ca-ccca-11ea-87d0-0242ac130003';

    protected static $errorNames = [
        self::INVALID_EAV_FAMILY_ERROR => 'INVALID_EAV_FAMILY_ERROR',
    ];

    /** @var string */
    public $message = 'This value should be an EAV data of family {{ family }}.';

    /** @var string */
    public $family;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'family';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['family'];
    }

    /**
     * @return array
     */
    public function getTargets()
    {
        return [static::CLASS_CONSTRAINT, static::PROPERTY_CONSTRAINT];
    }
}
