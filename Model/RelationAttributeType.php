<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * Type of attribute that embed an entity inside an other
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class RelationAttributeType extends AttributeType
{
    /**
     * @return bool
     */
    public function isRelation()
    {
        return true;
    }

    /**
     * @param AttributeInterface $attribute
     * @param mixed              $data
     *
     * @return array
     */
    public function getFormOptions(AttributeInterface $attribute, $data = null)
    {
        $formOptions = parent::getFormOptions($attribute, $data);
        if ($attribute->getFamily()) {
            $formOptions['family'] = $attribute->getFamily();
        }
        if ($attribute->getFamilies()) {
            $formOptions['families'] = $attribute->getFamilies();
        }

        return $formOptions;
    }
}
