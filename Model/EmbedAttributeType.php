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
     * AttributeType constructor.
     *
     * @param string $code
     * @param string $databaseType
     * @param string $formType
     * @param array  $formOptions
     */
    public function __construct($code, $databaseType, $formType, array $formOptions = [])
    {
        parent::__construct($code, $databaseType, $formType, $formOptions);
        $this->setEmbedded(true);
        $this->setRelation(false);
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
        $attribute->addValidationRule(['Valid' => []]);
    }
}
