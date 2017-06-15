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
