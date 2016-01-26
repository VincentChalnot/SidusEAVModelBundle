<?php

namespace Sidus\EAVModelBundle\Model;

interface AttributeInterface
{
    /**
     * @return string
     */
    public function getCode();

    /**
     * @return AttributeTypeInterface
     */
    public function getType();

    /**
     * @return string
     */
    public function getGroup();

    /**
     * @return array
     */
    public function getFormOptions();

    /**
     * @return array
     */
    public function getViewOptions();

    /**
     * @return boolean
     */
    public function isMultiple();

    /**
     * @return boolean
     */
    public function isRequired();

    /**
     * @return boolean
     */
    public function isUnique();
}
