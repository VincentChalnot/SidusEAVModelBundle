<?php

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Sidus\EAVModelBundle\Configuration\AttributeConfigurationHandler;
use Sidus\EAVModelBundle\Model\AttributeInterface;

/**
 * Convert request parameters in attributes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeParamConverter extends AbstractBaseParamConverter
{
    /** @var AttributeConfigurationHandler */
    protected $attributeConfigurationHandler;

    /**
     * @param AttributeConfigurationHandler $attributeConfigurationHandler
     */
    public function __construct(AttributeConfigurationHandler $attributeConfigurationHandler)
    {
        $this->attributeConfigurationHandler = $attributeConfigurationHandler;
    }

    /**
     * @param string $value
     *
     * @throws \UnexpectedValueException
     *
     * @return AttributeInterface
     */
    protected function convertValue($value)
    {
        return $this->attributeConfigurationHandler->getAttribute($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return AttributeInterface::class;
    }
}
