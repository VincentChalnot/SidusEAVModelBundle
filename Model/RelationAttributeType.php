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
}
