<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Model;

/**
 * Identifier attribute: special storing behavior: Only stored in the Data entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class IdentifierAttributeType extends AttributeType
{
    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute)
    {
        $attribute->setUnique(true);
        $attribute->setRequired(true);
        if ($attribute instanceof Attribute) {
            $attribute->setContextMask([]); // Empty context mask for identifiers
        }
    }
}
