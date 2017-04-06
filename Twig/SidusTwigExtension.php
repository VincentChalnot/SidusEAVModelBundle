<?php

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
