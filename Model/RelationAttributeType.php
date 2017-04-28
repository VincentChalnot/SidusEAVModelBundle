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
     *
     * @return array
     */
    public function getFormOptions(AttributeInterface $attribute)
    {
        $formOptions = parent::getFormOptions($attribute);
        $formOptions['attribute'] = $attribute;

        return $formOptions;
    }
}
