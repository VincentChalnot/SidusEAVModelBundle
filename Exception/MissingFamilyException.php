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
 * Exception launched when trying to fetch a missing family
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class MissingFamilyException extends \UnexpectedValueException implements EAVExceptionInterface
{
    /**
     * @param string $code
     */
    public function __construct($code)
    {
        parent::__construct("No family with code : {$code}");
    }
}
