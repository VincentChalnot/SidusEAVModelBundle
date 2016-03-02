<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\Value;

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
     * @param string $group
     */
    public function setGroup($group);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param string $code
     * @return mixed
     */
    public function getOption($code);

    /**
     * @param string $code
     * @param mixed $value
     */
    public function addOption($code, $value);

    /**
     * @param $data
     * @return array
     */
    public function getFormOptions($data = null);

    /**
     * @param string $code
     * @param mixed $value
     */
    public function addFormOption($code, $value);

    /**
     * @return array
     */
    public function getViewOptions();

    /**
     * @param string $code
     * @param mixed $value
     */
    public function addViewOption($code, $value);

    /**
     * @return boolean
     */
    public function isMultiple();

    /**
     * @param boolean $value
     */
    public function setMultiple($value);

    /**
     * @return boolean
     */
    public function isCollection();

    /**
     * @param boolean $value
     */
    public function setCollection($value);

    /**
     * @return boolean
     */
    public function isRequired();

    /**
     * @param boolean $value
     */
    public function setRequired($value);

    /**
     * @return boolean
     */
    public function isUnique();

    /**
     * @param boolean $value
     */
    public function setUnique($value);

    /**
     * @return array
     */
    public function getValidationRules();

    /**
     * @param array
     */
    public function addValidationRules(array $options);

    /**
     * @return array
     */
    public function getContextMask();

    /**
     * @param Value $value
     * @param array $context
     * @return bool
     */
    public function isContextMatching(Value $value, array $context);
}
