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

use Doctrine\ORM\EntityManagerInterface;
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
    protected $repository;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var DataLoaderInterface */
    protected $dataLoader;

    /**
     * @param FamilyRegistry         $familyRegistry
     * @param DataLoaderInterface    $dataLoader
     * @param EntityManagerInterface $entityManager
     * @param string                 $dataClass
     */
    public function __construct(
        FamilyRegistry $familyRegistry,
        DataLoaderInterface $dataLoader,
        EntityManagerInterface $entityManager,
        $dataClass
    ) {
        $this->familyRegistry = $familyRegistry;
        $this->dataLoader = $dataLoader;
        $this->repository = $entityManager->getRepository($dataClass);
    }

    /**
     * @param int|string     $value
     * @param ParamConverter $configuration
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @throws ORMException
     *
     * @return null|DataInterface
     */
    protected function convertValue($value, ParamConverter $configuration)
    {
        if (array_key_exists('family', $configuration->getOptions())) {
            $family = $this->familyRegistry->getFamily($configuration->getOptions()['family']);
            if ($family->isSingleton()) {
                $data = $this->repository->getInstance($family);
            } else {
                $data = $this->repository->findByIdentifier($family, $value, true);
            }
        } else {
            $data = $this->repository->find($value);
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
