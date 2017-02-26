<?php

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\ContextualValueInterface;

/**
 * Interface for attribute services
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
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
    public function getFamily();

    /**
     * @return array
     */
    public function getFamilies();

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
     *
     * @return mixed
     */
    public function getOption($code);

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addOption($code, $value);

    /**
     * @return string
     */
    public function getFormType();

    /**
     * @param mixed $data
     *
     * @return array
     */
    public function getFormOptions($data = null);

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addFormOption($code, $value);

    /**
     * @return array
     */
    public function getViewOptions();

    /**
     * @param string $code
     * @param mixed  $value
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
     * @param array $options
     */
    public function addValidationRule(array $options);

    /**
     * @param array $validationRules
     */
    public function setValidationRules(array $validationRules);

    /**
     * @return array
     */
    public function getContextMask();

    /**
     * @param ContextualValueInterface $value
     * @param array                    $context
     *
     * @return bool
     */
    public function isContextMatching(ContextualValueInterface $value, array $context);

    /**
     * @return mixed
     */
    public function getDefault();

    /**
     * @param array $configuration
     */
    public function mergeConfiguration(array $configuration);
}
