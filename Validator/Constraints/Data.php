<?php

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Define Data constraint
 *
 * @Annotation
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Data extends Constraint
{
    /**
     * @return string
     */
    public function validatedBy()
    {
        return 'sidus_data';
    }

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
