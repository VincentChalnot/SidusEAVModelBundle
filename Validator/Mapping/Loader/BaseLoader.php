<?php

namespace Sidus\EAVModelBundle\Validator\Mapping\Loader;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MappingException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

/**
 * Custom loader to manually call constraints on values based on their attributes
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class BaseLoader extends AbstractLoader
{

    /**
     * Loads validation metadata into a {@link ClassMetadata} instance.
     *
     * @param ClassMetadata $metadata The metadata to load
     *
     * @return bool Whether the loader succeeded
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        // throw an exception ?
    }

    /**
     * @param string $name
     * @param null   $options
     *
     * @return Constraint
     * @throws MappingException
     */
    public function newConstraint($name, $options = null)
    {
        return parent::newConstraint($name, $options);
    }
}
