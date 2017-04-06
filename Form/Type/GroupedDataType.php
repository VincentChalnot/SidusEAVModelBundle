<?php

namespace Sidus\EAVModelBundle\Form\Type;

use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

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
    public function buildValuesForm(FormBuilderInterface $builder, array $options = [])
    {
        /** @var FamilyInterface $family */
        $family = $options['family'];

        foreach ($family->getAttributes() as $attribute) {
            if ($attribute->getGroup()) {
                $groupName = $attribute->getGroup();
                if (!$builder->has($groupName)) {
                    $builder->add(
                        $groupName,
                        FormType::class,
                        [
                            'inherit_data' => true,
                        ]
                    );
                }
                $this->attributeFormBuilder->addAttribute($builder->get($groupName), $attribute, $options);
            } else {
                $this->attributeFormBuilder->addAttribute($builder, $attribute, $options);
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
