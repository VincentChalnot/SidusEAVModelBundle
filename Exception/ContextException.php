<?php

namespace Sidus\EAVModelBundle\Exception;

/**
 * General exception throw on context related errors
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextException extends \UnexpectedValueException implements EAVExceptionInterface
{
}
