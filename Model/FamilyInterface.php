<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\Context;
use Sidus\EAVModelBundle\Entity\ContextInterface;
use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Entity\Value;

interface FamilyInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getType();

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
     * @return Data
     */
    public function createData();

    /**
     * @param Data $data
     * @param AttributeInterface $attribute
     * @param ContextInterface $context
     * @return Value
     */
    public function createValue(Data $data, AttributeInterface $attribute, ContextInterface $context = null);

    /**
     * @return string
     */
    public function getDataClass();

    /**
     * @return string
     */
    public function getValueClass();

    /**
     * @return bool
     */
    public function isInstantiable();

    /**
     * @return Context
     */
    public function getDefaultContext();

    /**
     * @param array $contextValues
     * @return ContextInterface
     */
    public function createContext(array $contextValues);
}
