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
 * Define Data constraint
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @Annotation
 */
class Data extends Constraint
{
    /**
     * @return string
     */
    public function getTargets()
    {
        return static::CLASS_CONSTRAINT;
    }
}
