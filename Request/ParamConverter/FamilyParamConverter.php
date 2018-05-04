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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\BaseBundle\Request\ParamConverter\AbstractParamConverter;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Automatically convert request parameters in families
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyParamConverter extends AbstractParamConverter
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
     * @param string         $value
     * @param ParamConverter $configuration
     *
     * @return FamilyInterface
     */
    protected function convertValue($value, ParamConverter $configuration)
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
