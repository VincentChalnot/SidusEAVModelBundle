<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Exception;

/**
 * Exception thrown when trying to set invalid data for an attribute
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class InvalidValueDataException extends \UnexpectedValueException implements EAVExceptionInterface
{

}
