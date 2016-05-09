<?php

namespace Sidus\EAVModelBundle\Cache;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
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
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    /** @var EntityManager */
    protected $manager;

    /** @var string */
    protected $annotationDir;

    /**
     * @param FamilyConfigurationHandler $familyConfigurationHandler
     * @param EntityManager              $manager
     * @param string                     $rootDir
     */
    public function __construct(FamilyConfigurationHandler $familyConfigurationHandler, EntityManager $manager, $rootDir)
    {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
        $this->manager = $manager;
        $this->annotationDir = $rootDir.DIRECTORY_SEPARATOR.'annotations';
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
        return true;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        $baseDir = $this->annotationDir.DIRECTORY_SEPARATOR.'Sidus'.DIRECTORY_SEPARATOR.'EAV'.DIRECTORY_SEPARATOR;
        !@mkdir($baseDir, 0777, true) && !is_dir($baseDir);

        foreach ($this->familyConfigurationHandler->getFamilies() as $family) {
            $content = '<?php namespace Sidus\EAV; abstract class '.$family->getCode().' extends ';
            if ($family->getParent()) {
                $content .= $family->getParent()->getCode();
            } else {
                $content .= '\\'.$family->getDataClass();
            }
            $content .= " {\n";
            foreach ($family->getAttributes() as $attribute) {
                $content .= $this->generateGetAnnotation($family, $attribute);
                $content .= 'abstract public function get'.ucfirst($attribute->getCode()).'();'."\n";
                $content .= $this->generateSetAnnotation($family, $attribute);
                $content .= 'abstract public function set'.ucfirst($attribute->getCode()).'($value);'."\n";
            }

            $content .= "}\n";

            $this->writeFile($baseDir.$family->getCode().'.php', $content);
        }
    }

    /**
     * @param $filename
     * @param $content
     */
    protected function writeFile($filename, $content)
    {
        @file_put_contents($filename, $content);
    }

    protected function generateGetAnnotation(FamilyInterface $family, AttributeInterface $attribute)
    {
        $content = <<<EOT
/**
 * @return {$this->getPHPType($family, $attribute)}
 */

EOT;

        return $content;
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @return string
     */
    protected function generateSetAnnotation(FamilyInterface $family, AttributeInterface $attribute)
    {
        $content = <<<EOT
/**
 * @param {$this->getPHPType($family, $attribute)} \$value
 * @return {$family->getCode()}
 */

EOT;

        return $content;
    }

    /**
     * @param FamilyInterface    $family
     * @param AttributeInterface $attribute
     * @return string
     */
    protected function getPHPType(FamilyInterface $family, AttributeInterface $attribute)
    {
        $type = substr($attribute->getType()->getDatabaseType(), 0, -strlen('Value'));

        // Scalar types
        if (in_array($type, ['bool', 'integer', 'decimal', 'string', 'text'], true)) {
            if ($attribute->isMultiple()) {
                return 'array';
            }
            if ('text' === $type) {
                return 'string';
            } elseif ('decimal' === $type) {
                return 'double';
            }

            return $type;
        }
        if (in_array($type, ['date', 'datetime'], true)) {
            $type = '\DateTime';
            if ($attribute->isMultiple()) {
                $type .= '[]';
            }

            return $type;
        }
        if ('data' === $type) {
            $formOptions = $attribute->getFormOptions();
            // Simple case
            if (array_key_exists('family', $formOptions)) {
                $type = $formOptions['family'];
                if ($attribute->isMultiple()) {
                    $type .= '[]';
                }

                return $type;
            }

            // Multiple families (some case)
            if (array_key_exists('families', $formOptions)) {
                $types = $formOptions['families'];
                if (!is_array($types)) {
                    return 'mixed'; // Shouldn't happen
                }
                if ($attribute->isMultiple()) {
                    foreach ($types as &$type) {
                        $type .= '[]';
                    }
                }

                return implode('|', $types);
            }

            // Couldn't find any family (rare case)
            if ($attribute->isMultiple()) {
                return 'array';
            }

            return 'mixed';
        }

        // Then there are the custom relation cases:
        $type = $this->getTargetClass($family, $attribute);
        if ($type) {
            return $type;
        }

        // Fallback in any other case
        if ($attribute->isMultiple()) {
            return 'array';
        }

        return 'mixed';
    }

    /**
     * @param FamilyInterface    $parentFamily
     * @param AttributeInterface $attribute
     * @return string
     */
    protected function getTargetClass(FamilyInterface $parentFamily, AttributeInterface $attribute)
    {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata $classMetadata */
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
        if ($attribute->isMultiple()) {
            $type .= '[]';
        }

        return '\\'.$type;
    }
}
