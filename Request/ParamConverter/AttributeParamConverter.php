<?php

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Sidus\EAVModelBundle\Configuration\AttributeConfigurationHandler;

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

    protected function convertValue($value)
    {
        return $this->attributeConfigurationHandler->getAttribute($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return 'Sidus\EAVModelBundle\Model\AttributeInterface';
    }
}
