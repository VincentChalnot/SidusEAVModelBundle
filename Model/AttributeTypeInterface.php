<?php

namespace Sidus\EAVModelBundle\Model;

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
     * @param AttributeInterface $attribute
     */
    public function setAttributeDefaults(AttributeInterface $attribute);

    /**
     * @param $data
     * @return array
     */
    public function getFormOptions($data);
}
