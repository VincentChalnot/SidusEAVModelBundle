<?php

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Data extends Constraint
{
    public function validatedBy()
    {
        return 'sidus_data';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
