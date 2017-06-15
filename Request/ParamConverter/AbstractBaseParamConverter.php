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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Common pattern for param converters
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractBaseParamConverter implements ParamConverterInterface
{
    /**
     * Stores the object in the request.
     *
     * @param Request        $request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \InvalidArgumentException
     *
     * @return bool True if the object has been successfully set, else false
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $this->getRequestAttributeName($request, $configuration);

        if (!$request->attributes->has($param)) {
            return false;
        }

        $value = $request->attributes->get($param);

        if (!$value && $configuration->isOptional()) {
            return false;
        }

        $convertedValue = $this->convertValue($value);

        if (null === $convertedValue && false === $configuration->isOptional()) {
            throw new NotFoundHttpException(
                "Unable to find '{$configuration->getClass()}' with identifier '{$value}' not found"
            );
        }

        $request->attributes->set($configuration->getName(), $convertedValue);
        if ($param !== $configuration->getName()) {
            $request->attributes->set($param, $convertedValue);
        }

        return true;
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration Should be an instance of ParamConverter
     *
     * @return bool True if the object is supported, else false
     */
    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() && is_a($configuration->getClass(), $this->getClass(), true);
    }

    /**
     * @param Request        $request
     * @param ParamConverter $configuration
     *
     * @return string
     */
    protected function getRequestAttributeName(Request $request, ParamConverter $configuration)
    {
        $param = $configuration->getName();
        if (array_key_exists('id', $configuration->getOptions())) {
            $param = $configuration->getOptions()['id'];
        }

        return $param;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    abstract protected function convertValue($value);

    /**
     * @return mixed
     */
    abstract protected function getClass();
}
