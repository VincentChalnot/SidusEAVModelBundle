<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\Data;
use Sidus\EAVModelBundle\Entity\Value;

/**
 * Interface for family services
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
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
     * @param Data               $data
     * @param AttributeInterface $attribute
     * @param array              $context
     * @return Value
     */
    public function createValue(Data $data, AttributeInterface $attribute, array $context = null);

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
     * @return array
     */
    public function getDefaultContext();
}
