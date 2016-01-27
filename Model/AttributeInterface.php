<?php

namespace Sidus\EAVModelBundle\Model;

use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    /**
     * @return ValidatorInterface[]
     */
    public function getValidationRules();
}
