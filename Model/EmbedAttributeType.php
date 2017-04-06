<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * Type of attribute that embed an entity inside an other
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EmbedAttributeType extends AttributeType
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


    /**
     * @param AttributeInterface $attribute
     *
     * @throws \LogicException
     *
     * @return array
     */
    public function getFormOptions(AttributeInterface $attribute)
    {
        $formOptions = parent::getFormOptions($attribute);
        if ($attribute->getOption('allowed_families')) {
            $families = $attribute->getOption('allowed_families');
            if (1 < count($families)) {
                $m = "Standard embed attribute '{$attribute->getCode()}' doesn't supports more that one";
                $m .= ' allowed_families in options';
                throw new \LogicException($m);
            }
            reset($families);
            $formOptions['family'] = current($families);
        }

        return $formOptions;
    }
}
