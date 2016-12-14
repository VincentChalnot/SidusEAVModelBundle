<?php

namespace Sidus\EAVModelBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Allow multiple choices to handle Doctrine collections
 */
class ChoiceTypeExtension extends AbstractTypeExtension
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($toView) {
                if ($toView instanceof Collection) {
                    return $toView->toArray();
                }

                return $toView;
            },
            function ($toModel) {
                return $toModel;
            }
        ));
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return ChoiceType::class;
    }
}
