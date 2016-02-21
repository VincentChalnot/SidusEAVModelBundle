<?php

namespace Sidus\EAVModelBundle\Event;


use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

class DoctrineMetadataListener
{
    const BASE_DATA_CLASS = 'Sidus\EAVModelBundle\Entity\Data';
    const BASE_VALUE_CLASS = 'Sidus\EAVModelBundle\Entity\Value';
    const BASE_CONTEXT_CLASS = 'Sidus\EAVModelBundle\Entity\Context';

    /** @var array */
    protected $mapping;

    /**
     * DoctrineMetadataListener constructor.
     * @param string $dataClass
     * @param string $valueClass
     * @param string $contextClass
     */
    public function __construct($dataClass, $valueClass, $contextClass)
    {
        $this->mapping = [
            self::BASE_DATA_CLASS => $dataClass,
            self::BASE_VALUE_CLASS => $valueClass,
            self::BASE_CONTEXT_CLASS => $contextClass,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        foreach ($metadata->associationMappings as $fieldName => $association) {
            if (!empty($association['targetEntity'])) {
                foreach ($this->mapping as $class => $override) {
                    if ($association['targetEntity'] === $class) {
                        $metadata->associationMappings[$fieldName]['targetEntity'] = $override;
                        continue;
                    }
                }
            }
            if (!empty($association['sourceEntity'])) {
                foreach ($this->mapping as $class => $override) {
                    if ($association['sourceEntity'] === $class) {
                        $metadata->associationMappings[$fieldName]['sourceEntity'] = $override;
                        continue;
                    }
                }
            }
        }
    }
}
