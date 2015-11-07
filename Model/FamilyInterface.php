<?php

namespace Sidus\EAVModelBundle\Model;

interface FamilyInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return AttributeInterface
     */
    public function getAttributeAsLabel();

    /**
     * @return AttributeInterface[]
     */
    public function getAttributes();

    /**
     * @param $code
     * @return AttributeInterface
     */
    public function getAttribute($code);

    /**
     * @param $code
     * @return bool
     */
    public function hasAttribute($code);

    /**
     * @return FamilyInterface
     */
    public function getParent();

    /**
     * @return FamilyInterface[]
     */
    public function getChildren();
}
