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

namespace Sidus\EAVModelBundle\Translator;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Used to try multiple translations with fallback
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
trait TranslatableTrait
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Will check the translator for the provided keys and humanize the code if no translation is found
     *
     * @param string|array $tIds
     * @param array        $parameters
     * @param string       $fallback
     * @param bool         $humanizeFallback
     *
     * @return string
     */
    protected function tryTranslate($tIds, array $parameters = [], $fallback = null, $humanizeFallback = true)
    {
        foreach ((array) $tIds as $tId) {
            try {
                if ($this->translator instanceof TranslatorBagInterface) {
                    if ($this->translator->getCatalogue()->has($tId)) {
                        /** @noinspection PhpUndefinedMethodInspection */

                        return $this->translator->trans($tId, $parameters);
                    }
                } elseif ($this->translator instanceof TranslatorInterface) {
                    $label = $this->translator->trans($tId, $parameters);
                    if ($label !== $tId) {
                        return $label;
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // Do nothing
            }
        }

        if ($fallback === null) {
            return null;
        }
        if (!$humanizeFallback) {
            return $fallback;
        }
        $pattern = '/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]|\d{1,}/';

        return str_replace('_', ' ', preg_replace($pattern, ' $0', $fallback));
    }
}
