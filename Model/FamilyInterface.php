<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Entity\Value;

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
     * @param string $code
     * @return AttributeInterface
     */
    public function getAttribute($code);

    /**
     * @param string $code
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

    /**
     * @return array
     */
    public function getMatchingCodes();

    /**
     * @param FamilyInterface $child
     */
    public function addChild(FamilyInterface $child);

    /**
     * @param Data $data
     * @param AttributeInterface $attribute
     * @return Value
     */
    public function createValue(Data $data, AttributeInterface $attribute);

    /**
     * @return string
     */
    public function getValueClass();

    /**
     * @return bool
     */
    public function isInstantiable();
}
