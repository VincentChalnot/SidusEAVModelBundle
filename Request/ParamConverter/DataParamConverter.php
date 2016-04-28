<?php

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Convert request parameters in data
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataParamConverter extends AbstractBaseParamConverter
{
    /** @var DataRepository */
    protected $dataRepository;

    /**
     * DataParamConverter constructor.
     *
     * @param DataRepository $dataRepository
     */
    public function __construct(DataRepository $dataRepository)
    {
        $this->dataRepository = $dataRepository;
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
        if ($request->attributes->has('id')) {
            $configuration->setName('id');
        }
        if ($request->attributes->has('dataId')) {
            $configuration->setName('dataId');
        }
        if (!parent::apply($request, $configuration)) {
            return false;
        }
        if ($originalName !== $configuration->getName()) {
            $request->attributes->set($originalName, $request->attributes->get($configuration->getName()));
        }

        return true;
    }

    protected function convertValue($value)
    {
        return $this->dataRepository->find($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return DataInterface::class;
    }
}
