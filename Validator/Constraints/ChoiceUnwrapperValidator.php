<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
