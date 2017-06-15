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

namespace Sidus\EAVModelBundle\Twig;

use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Translator\TranslatableTrait;
use Symfony\Component\Translation\TranslatorInterface;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Add useful functions in twig to access the EAV configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusTwigExtension extends Twig_Extension
{
    use TranslatableTrait;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /**
     * @param FamilyRegistry      $familyRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(FamilyRegistry $familyRegistry, TranslatorInterface $translator)
    {
        $this->familyRegistry = $familyRegistry;
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_eav_model';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('get_families', [$this->familyRegistry, 'getFamilies']),
            new Twig_SimpleFunction('get_root_families', [$this->familyRegistry, 'getRootFamilies']),
            new Twig_SimpleFunction('get_family', [$this->familyRegistry, 'getFamily']),
            new Twig_SimpleFunction('tryTrans', [$this, 'tryTrans']),
        ];
    }

    /**
     * @param string|array $tIds
     * @param array        $parameters
     * @param string|null  $fallback
     * @param bool         $humanizeFallback
     *
     * @return string
     */
    public function tryTrans($tIds, array $parameters = [], $fallback = null, $humanizeFallback = true)
    {
        return $this->tryTranslate($tIds, $parameters, $fallback, $humanizeFallback);
    }
}
