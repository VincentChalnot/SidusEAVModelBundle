<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;

/**
 * Allows to input the same kind of data in a choice validator than in a ChoiceType (unwrap choice groups)
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 *
 * @Annotation
 */
class ChoiceUnwrapperValidator extends ChoiceValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof ChoiceUnwrapper) {
            if ((null === $value || '' === $value) && $constraint->allowBlank) {
                return;
            }
            $resolvedChoices = [];
            if (is_array($constraint->choices)) {
                /** @noinspection ForeachSourceInspection */
                foreach ($constraint->choices as $choice) {
                    if (is_array($choice)) {
                        /** @var array $choice */
                        foreach ($choice as $subChoice) {
                            $resolvedChoices[] = $subChoice;
                        }
                    } else {
                        $resolvedChoices[] = $choice;
                    }
                }

                $constraint->choices = $resolvedChoices;
            }
        }

        parent::validate($value, $constraint);
    }
}
