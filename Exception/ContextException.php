<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Exception;

/**
 * General exception throw on context related errors
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ContextException extends \UnexpectedValueException implements EAVExceptionInterface
{
}
