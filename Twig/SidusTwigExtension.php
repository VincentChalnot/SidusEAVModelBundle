<?php

namespace Sidus\EAVModelBundle\Twig;

use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Symfony\Component\Form\FormView;
use Twig_Extension;
use Twig_SimpleFunction;

class SidusTwigExtension extends Twig_Extension
{
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    function __construct(FamilyConfigurationHandler $familyConfigurationHandler)
    {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
    }

    public function getName()
    {
        return 'sidus_eav_model';
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('form_has_error', [$this, 'formHasError']),
            new Twig_SimpleFunction('get_families', [$this, 'getFamilies']),
        ];
    }

    public function formHasError(FormView $form)
    {
        if (0 < count($form->vars['errors'])) {
            return true;
        }
        foreach ($form->children as $child) {
            if ($this->formHasError($child)) {
                return true;
            }
        }
        return false;
    }

    public function getFamilies()
    {
        return $this->familyConfigurationHandler->getFamilies();
    }
}