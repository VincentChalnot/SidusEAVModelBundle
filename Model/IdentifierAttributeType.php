<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * Identifier attribute: special storing behavior: Only stored in the Data entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class IdentifierAttributeType extends AttributeType
{
    /**
     * @inheritdoc
     *
     * @throws \UnexpectedValueException
     */
    public function __construct($code, $databaseType, $formType, array $formOptions = [])
    {
        if (!in_array($databaseType, ['stringIdentifier', 'integerIdentifier'], true)) {
            $m = "Identifier attribute {$code} can only be a stringIdentifier or an integerIdentifier, '{$databaseType}' given";
            throw new \UnexpectedValueException($m);
        }
        parent::__construct($code, $databaseType, $formType, $formOptions);
        $this->isRelation = false;
    }

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
        $attribute->setUnique(true);
        $attribute->setRequired(true);
    }
}
