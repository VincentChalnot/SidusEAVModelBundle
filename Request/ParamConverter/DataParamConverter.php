<?php
/*
 *  Sidus/EAVModelBundle : EAV Data management in Symfony 3
 *  Copyright (C) 2015-2017 Vincent Chalnot
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sidus\EAVModelBundle\Request\ParamConverter;

use Doctrine\Bundle\DoctrineBundle\Registry;
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
