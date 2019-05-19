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
 * Exception thrown when trying to fetch a missing attribute
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingAttributeTypeException extends \UnexpectedValueException implements EAVExceptionInterface
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No attribute type with code : {$code}");
    }
}
