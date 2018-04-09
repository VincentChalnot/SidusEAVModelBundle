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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\BaseBundle\Request\ParamConverter\AbstractParamConverter;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\DataRepository;
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

    /**
     * @param string   $dataClass
     * @param Registry $doctrine
     */
    public function __construct($dataClass, Registry $doctrine)
    {
        $this->dataRepository = $doctrine->getRepository($dataClass);
    }

    /**
     * Stores the object in the request.
     *
     * @param Request        $request       The request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \InvalidArgumentException
     *
     * @return bool True if the object has been successfully set, else false
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $originalName = parent::getRequestAttributeName($request, $configuration);
        $fallbackName = $this->getRequestAttributeName($request, $configuration);
        if (!parent::apply($request, $configuration)) {
            return false;
        }
        if ($originalName !== $fallbackName) {
            $request->attributes->set($originalName, $request->attributes->get($fallbackName));
        }

        return true;
    }

    /**
     * @param int|string $value
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return null|DataInterface
     */
    protected function convertValue($value)
    {
        return $this->dataRepository->loadFullEntity($value);
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
