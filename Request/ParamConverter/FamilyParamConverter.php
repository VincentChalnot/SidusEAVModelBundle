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
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
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
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /**
     * @param FamilyRegistry $familyRegistry
     */
    public function __construct(FamilyRegistry $familyRegistry)
    {
        $this->familyRegistry = $familyRegistry;
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
        return $this->familyRegistry->getFamily($value);
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return FamilyInterface::class;
    }
}
