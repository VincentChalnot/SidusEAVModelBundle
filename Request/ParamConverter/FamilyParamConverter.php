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
     * Stores the object in the request.
     *
     * @param Request $request The request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     * @throws \InvalidArgumentException
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $originalName = $configuration->getName();
        if ($request->attributes->has('familyCode')) {
            $configuration->setName('familyCode');
        }
        if (!parent::apply($request, $configuration)) {
            return false;
        }
        if ($originalName !== $configuration->getName()) {
            $request->attributes->set($originalName, $request->attributes->get($configuration->getName()));
        }
        return true;
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return 'Sidus\EAVModelBundle\Model\FamilyInterface';
    }
}
