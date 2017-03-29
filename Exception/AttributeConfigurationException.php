<?php

namespace Sidus\EAVModelBundle\Exception;

/**
 * Thrown on model compilation when something is not right with an attribute configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeConfigurationException extends \UnexpectedValueException implements EAVExceptionInterface
{

}
