<?php

namespace Sidus\EAVModelBundle\Form\Type;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;

/**
 * Alternative data form with sub-form corresponding to attribute's groups
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class GroupedDataType extends DataType
{
    /**
     * {@inheritdoc}
     */
    public function buildValuesForm(
        FormInterface $form,
        FamilyInterface $family,
        DataInterface $data = null,
        array $options = []
    ) {
        foreach ($family->getAttributes() as $attribute) {
            if ($attribute->getGroup()) {
                $groupName = $attribute->getGroup();
                if (!$form->has($groupName)) {
                    $form->add(
                        $groupName,
                        FormType::class,
                        [
                            'inherit_data' => true,
                        ]
                    );
                }
                $this->addAttribute($form->get($groupName), $attribute, $data, $options);
            } else {
                $this->addAttribute($form, $attribute, $data, $options);
            }
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_grouped_data';
    }
}
