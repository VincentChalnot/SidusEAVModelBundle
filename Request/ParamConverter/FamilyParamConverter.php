<?php

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Symfony\Component\HttpFoundation\Request;

class FamilyParamConverter extends BaseParamConverter
{
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /**
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     */
    public function __construct(FamilyConfigurationHandler $familyConfigurationHandler)
    {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
    }

    protected function convertValue($value)
    {
        return $this->familyConfigurationHandler->getFamily($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return 'Sidus\EAVModelBundle\Model\FamilyInterface';
    }
}
