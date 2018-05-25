<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Cache annotation generator (using interfaces)
 * This is NOT code generation, the final result is only meant to aid the IDE to autocomplete magic methods
 * Do NOT use any of these interface outside of annotations.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AnnotationGenerator implements CacheWarmerInterface
{
    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var EntityManagerInterface */
    protected $manager;

    /** @var string */
    protected $annotationDir;

    /**
     * @param FamilyRegistry         $familyRegistry
     * @param EntityManagerInterface $manager
     * @param string                 $varDir
     */
    public function __construct(FamilyRegistry $familyRegistry, EntityManagerInterface $manager, $varDir)
    {
        $this->familyRegistry = $familyRegistry;
        $this->manager = $manager;
        $this->annotationDir = $varDir.DIRECTORY_SEPARATOR.'annotations';
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * Optional warmers can be ignored on certain conditions.
     *
     * A warmer should return true if the cache can be
     * generated incrementally and on-demand.
     *
     * @return bool true if the warmer is optional, false otherwise
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     *
     * @throws \UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function warmUp($cacheDir)
    {
        $baseDir = $this->annotationDir.DIRECTORY_SEPARATOR.'Sidus'.DIRECTORY_SEPARATOR.'EAV'.DIRECTORY_SEPARATOR;
        if (!@mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
            throw new \RuntimeException("Unable to create annotations directory: {$baseDir}");
        }

        foreach ($this->familyRegistry->getFamilies() as $family) {
            $content = $this->getFileHeader($family);

            foreach ($family->getAttributes() as $attribute) {
                $content .= $this->getAttributeMethods($family, $attribute);
            }

            $content .= "}\n";

            $this->writeFile($baseDir.$family->getCode().'.php', $content);
        }
    }

    /**
     * @param FamilyInterface $family
     *
     * @return string
     */
    protected function getFileHeader(FamilyInterface $family)
    {
        $content = <<<EOT
<?php

namespace Sidus\EAV;

abstract class {$family->getCode()} extends 
EOT;
        if ($family->getParent()) {
            $content .= $family->getParent()->getCode();
        } else {
            $content .= '\\'.$family->getDataClass();
        }
        $content .= "\n{\n";

        return $content;
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     *
     * @throws \UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function getAttributeMethods(FamilyInterface $family, AttributeInterface $attribute)
    {
        if ($this->isAttributeInherited($family, $attribute)) {
            return '';
        }
        $content = '';
        $dataClass = new \ReflectionClass($family->getDataClass());

        $getter = 'get'.ucfirst($attribute->getCode());
        if (!$dataClass->hasMethod($getter)) {
            $content .= $this->generateGetAnnotation($family, $attribute);
            $content .= "abstract public function {$getter}(array \$context = null);\n\n";
        }

        $setter = 'set'.ucfirst($attribute->getCode());
        if (!$dataClass->hasMethod($setter)) {
            $content .= $this->generateSetAnnotation($family, $attribute);
            $content .= 'abstract public function set'.ucfirst($attribute->getCode());
            $content .= '($value, array $context = null);'."\n\n";
        }

        if ($attribute->isCollection()) {
            // Adder and remover
            $setter = 'add'.ucfirst($attribute->getCode());
            if (!$dataClass->hasMethod($setter)) {
                $content .= $this->generateSetAnnotation($family, $attribute, true);
                $content .= 'abstract public function add'.ucfirst($attribute->getCode());
                $content .= '($value, array $context = null);'."\n\n";
            }

            $setter = 'remove'.ucfirst($attribute->getCode());
            if (!$dataClass->hasMethod($setter)) {
                $content .= $this->generateSetAnnotation($family, $attribute, true);
                $content .= 'abstract public function remove'.ucfirst($attribute->getCode());
                $content .= '($value, array $context = null);'."\n\n";
            }
        }

        return $content;
    }

    /**
     * @param string $filename
     * @param string $content
     *
     * @throws \RuntimeException
     */
    protected function writeFile($filename, $content)
    {
        if (!@file_put_contents($filename, $content)) {
            throw new \RuntimeException("Unable to write annotation file: {$filename}");
        }
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param bool               $forceSingle
     *
     * @throws \UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     *
     * @return string
     */
    protected function generateGetAnnotation(
        FamilyInterface $family,
        AttributeInterface $attribute,
        $forceSingle = false
    ) {
        $content = <<<EOT
/**
 * @param array|null \$context
 *
 * @return {$this->getPHPType($family, $attribute, $forceSingle)}
 */

EOT;

        return $content;
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param bool               $forceSingle
     *
     * @throws \UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     *
     * @return string
     */
    protected function generateSetAnnotation(
        FamilyInterface $family,
        AttributeInterface $attribute,
        $forceSingle = false
    ) {
        $content = <<<EOT
/**
 * @param {$this->getPHPType($family, $attribute, $forceSingle)} \$value
 * @param array|null \$context
 *
 * @return {$family->getCode()}
 */

EOT;

        return $content;
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @param bool               $forceSingle
     *
     * @throws \UnexpectedValueException
     * @throws \Sidus\EAVModelBundle\Exception\MissingFamilyException
     *
     * @return string
     */
    protected function getPHPType(
        FamilyInterface $family,
        AttributeInterface $attribute,
        $forceSingle = false
    ) {
        $type = substr($attribute->getType()->getDatabaseType(), 0, -\strlen('Value'));
        $collection = $attribute->isCollection() && !$forceSingle;

        // Scalar types
        if (\in_array($type, ['bool', 'integer', 'decimal', 'string', 'text'], true)) {
            if ($collection) {
                return 'array';
            }
            if ('text' === $type) {
                return 'string';
            }
            if ('decimal' === $type) {
                return 'double';
            }

            return $type;
        }
        if (\in_array($type, ['date', 'datetime'], true)) {
            /** @noinspection ClassConstantCanBeUsedInspection */
            $type = '\DateTime';
            if ($collection) {
                $type .= '[]';
            }

            return $type;
        }
        if ('data' === $type || 'constrainedData' === $type) {
            $familyCodes = $attribute->getOption('allowed_families');
            if ($familyCodes) {
                if (!\is_array($familyCodes)) {
                    $familyCodes = [$familyCodes];
                }
                $types = [];
                foreach ($familyCodes as $familyCode) {
                    $types[] = $familyCode.($collection ? '[]' : '');
                    $family = $this->familyRegistry->getFamily($familyCode);
                    if ($family->getDataClass()) {
                        $types[] = '\\'.ltrim($family->getDataClass(), '\\').($collection ? '[]' : '');
                    }
                }

                return implode('|', $types);
            }

            // Couldn't find any family (rare case)
            if ($collection) {
                return 'array';
            }

            return 'mixed';
        }

        // Then there are the custom relation cases:
        $type = $this->getTargetClass($family, $attribute, $forceSingle);
        if ($type) {
            return $type;
        }

        // Fallback in any other case
        if ($collection) {
            return 'array';
        }

        return 'mixed';
    }

    /**
     * @param FamilyInterface    $parentFamily
     * @param AttributeInterface $attribute
     * @param bool               $forceSingle
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    protected function getTargetClass(
        FamilyInterface $parentFamily,
        AttributeInterface $attribute,
        $forceSingle = false
    ) {
        $classMetadata = $this->manager->getClassMetadata($parentFamily->getValueClass());
        try {
            $mapping = $classMetadata->getAssociationMapping($attribute->getType()->getDatabaseType());
        } catch (MappingException $e) {
            return null;
        }
        if (empty($mapping['targetEntity'])) {
            return null;
        }

        $type = $mapping['targetEntity'];
        if (!$forceSingle && $attribute->isCollection()) {
            $type .= '[]';
        }

        return '\\'.$type;
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     *
     * @return bool
     */
    protected function isAttributeInherited(FamilyInterface $family, AttributeInterface $attribute)
    {
        if (!$family->getParent()) {
            return false;
        }
        if ($family->getParent()->hasAttribute($attribute->getCode())) {
            return true;
        }

        return $this->isAttributeInherited($family->getParent(), $attribute);
    }
}
