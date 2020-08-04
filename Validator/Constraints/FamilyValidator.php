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

use Exception;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Check if an object is of the proper family
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param DataInterface|null $value      The value that should be validated
     * @param Constraint         $constraint The constraint for the validation
     *
     * @throws Exception
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Family) {
            throw new UnexpectedTypeException($constraint, Family::class);
        }

        if (null === $value) {
            return;
        }

        if ($value instanceof DataInterface && $value->getFamilyCode() === $constraint->family) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setParameter('{{ family }}', $constraint->family)
            ->setCode(Family::INVALID_EAV_FAMILY_ERROR)
            ->addViolation();
    }
}
