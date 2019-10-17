<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Registry;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Sidus\EAVModelBundle\Annotation\Family as FamilyAnnotation;
use function count;

/**
 * Container for families
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class FamilyRegistry
{
    /** @var AnnotationReader */
    protected $annotationReader;

    /** @var FamilyInterface[] */
    protected $families = [];

    /**
     * @param AnnotationReader $annotationReader
     */
    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param FamilyInterface $family
     */
    public function addFamily(FamilyInterface $family)
    {
        $this->families[$family->getCode()] = $family;
    }

    /**
     * @return FamilyInterface[]
     */
    public function getFamilies()
    {
        return $this->families;
    }

    /**
     * @return array
     */
    public function getFamilyCodes()
    {
        return array_keys($this->families);
    }

    /**
     * @param string $code
     *
     * @throws MissingFamilyException
     *
     * @return FamilyInterface
     */
    public function getFamily($code)
    {
        if (!$this->hasFamily($code)) {
            throw new MissingFamilyException($code);
        }

        return $this->families[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasFamily($code)
    {
        return array_key_exists($code, $this->families);
    }

    /**
     * Get all instantiable families with no parent
     *
     * @return array
     */
    public function getRootFamilies()
    {
        $root = [];
        foreach ($this->getFamilies() as $family) {
            if ($family->isInstantiable()) {
                $p = $family->getParent();
                if (!$p || ($p && !$p->isInstantiable())) {
                    $root[$family->getCode()] = $family;
                }
            }
        }

        return $root;
    }

    /**
     * @param FamilyInterface $family
     *
     * @return array
     */
    public function getByParent(FamilyInterface $family)
    {
        $families = [];
        foreach ($this->families as $subFamily) {
            if ($subFamily->getParent() && $subFamily->getParent()->getCode() === $family->getCode()) {
                $families[$subFamily->getCode()] = $subFamily;
            }
        }

        return $families;
    }

    /**
     * @param string $class
     *
     * @throws RuntimeException
     *
     * @return FamilyInterface
     */
    public function getFamilyByDataClass($class)
    {
        try {
            $annotation = $this->annotationReader->getClassAnnotation(
                new ReflectionClass($class),
                FamilyAnnotation::class
            );
        } catch (ReflectionException $e) {
            throw new RuntimeException("Error reading annotations from class {$class}", 0, $e);
        }

        if ($annotation instanceof FamilyAnnotation) {
            return $this->getFamily($annotation->familyCode);
        }

        $matching = [];
        foreach ($this->getFamilies() as $family) {
            if ($family->getDataClass() === $class) {
                $matching[] = $family;
            }
        }
        if (1 === count($matching)) {
            return reset($matching);
        }

        $m = "Unable to resolve family from data class '{$class}', ";
        $m .= 'please use the @Family annotation on a dedicated data class';
        throw new RuntimeException($m);
    }
}
