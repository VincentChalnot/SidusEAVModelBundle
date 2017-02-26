<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * Type of attribute that embed an entity inside an other
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EmbedAttributeType extends RelationAttributeType
{
    /**
     * @return bool
     */
    public function isRelation()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isEmbedded()
    {
        return true;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
        $attribute->addValidationRule(['Valid' => []]);
    }
}
