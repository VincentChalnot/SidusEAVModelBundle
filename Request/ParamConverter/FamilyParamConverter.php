<?php

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Automatically convert request parameters in families
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyParamConverter extends AbstractBaseParamConverter
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

    /**
     * Stores the object in the request.
     *
     * @param Request        $request       The request
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
     * @param string $value
     *
     * @return FamilyInterface
     * @throws MissingFamilyException
     */
    protected function convertValue($value)
    {
        return $this->familyConfigurationHandler->getFamily($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return FamilyInterface::class;
    }
}
