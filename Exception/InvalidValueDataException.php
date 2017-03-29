<?php
namespace Sidus\EAVModelBundle\Exception;

/**
 * Exception thrown when trying to set invalid data for an attribute
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class InvalidValueDataException extends \UnexpectedValueException implements EAVExceptionInterface
{

}
