<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Model;

use Sidus\EAVModelBundle\Entity\ContextualValueInterface;
use Sidus\EAVModelBundle\Exception\AttributeConfigurationException;
use Sidus\EAVModelBundle\Exception\ContextException;

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
     * The family that carries the attribute
     *
     * @return FamilyInterface
     */
    public function getFamily();

    /**
     * @param FamilyInterface $family
     */
    public function setFamily(FamilyInterface $family);

    /**
     * Optional, used to separate attributes in different groups
     *
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
     * Generic options that can be used in any applications using the EAV Model
     *
     * @param string $code
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($code, $default = null);

    /**
     * @param string $code
     * @param mixed  $value
     */
    public function addOption($code, $value);

    /**
     * The identifier of the Symfony form type
     *
     * @return string
     */
    public function getFormType();

    /**
     * The options passed to the form
     *
     * @return array
     */
    public function getFormOptions();

    /**
     * Passed as form options to the form widget
     *
     * @param string $code
     * @param mixed  $value
     */
    public function addFormOption($code, $value);

    /**
     * This option defines if an attribute is meant to be edited with multiple form widgets in forms
     * A multiple attribute will automatically be a collection but a collection can be edited as a single widget and
     * therefore NOT be "multiple".
     *
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
     * @throws ContextException
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
     *
     * @throws AttributeConfigurationException
     */
    public function mergeConfiguration(array $configuration);
}
