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
 * Thrown on model compilation when something is not right with an attribute configuration
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AttributeConfigurationException extends \UnexpectedValueException implements EAVExceptionInterface
{

}
