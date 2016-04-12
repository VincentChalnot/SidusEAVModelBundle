<?php

namespace Sidus\EAVModelBundle\Model;

/**
 * Interface for attribute types services
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface AttributeTypeInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getDatabaseType();

    /**
     * @return string
     */
    public function getFormType();

    /**
     * @return bool
     */
    public function isEmbedded();

    /**
     * @return bool
     */
    public function isRelation();

    /**
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute);

    /**
     * @param mixed $data
     * @return array
     */
    public function getFormOptions($data);
}
