<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\BaseBundle\Request\ParamConverter\AbstractParamConverter;
use Sidus\EAVModelBundle\Doctrine\DataLoaderInterface;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * Convert request parameters in data
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataParamConverter extends AbstractParamConverter
{
    /** @var DataRepository */
    protected $dataRepository;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var DataLoaderInterface */
    protected $dataLoader;

    /**
     * @param string              $dataClass
     * @param Registry            $doctrine
     * @param FamilyRegistry      $familyRegistry
     * @param DataLoaderInterface $dataLoader
     */
    public function __construct(
        $dataClass,
        Registry $doctrine,
        FamilyRegistry $familyRegistry,
        DataLoaderInterface $dataLoader
    ) {
        $this->dataRepository = $doctrine->getRepository($dataClass);
        $this->familyRegistry = $familyRegistry;
        $this->dataLoader = $dataLoader;
    }

    /**
     * @param int|string     $value
     * @param ParamConverter $configuration
     *
     * @throws ORMException
     *
     * @return null|DataInterface
     */
    protected function convertValue($value, ParamConverter $configuration)
    {
        if (array_key_exists('family', $configuration->getOptions())) {
            $family = $this->familyRegistry->getFamily($configuration->getOptions()['family']);
            $data = $this->dataRepository->findByIdentifier($family, $value, true);
        } else {
            $data = $this->dataRepository->find($value);
        }
        $this->dataLoader->loadSingle($data);

        return $data;
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return DataInterface::class;
    }

    /**
     * Allow fallback to "dataId" or "id" in case no attribute is found
     *
     * @deprecated This custom behavior will be deprecated in futur versions
     *
     * @param Request        $request
     * @param ParamConverter $configuration
     *
     * @return string
     */
    protected function getRequestAttributeName(Request $request, ParamConverter $configuration)
    {
        $param = parent::getRequestAttributeName($request, $configuration);
        if (!$request->attributes->has($param)) {
            if ($request->attributes->has('dataId')) {
                $param = 'dataId';
            } elseif ($request->attributes->has('id')) {
                $param = 'id';
            }
        }

        return $param;
    }
}
