<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * Type of attribute that embed an entity inside an other
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ChoiceAttributeType extends AttributeType
{
    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
        $formOptions = $attribute->getFormOptions();
        if (is_array($formOptions) && array_key_exists('multiple', $formOptions) && $formOptions['multiple']) {
            $attribute->setCollection(true);
        }
    }
}
