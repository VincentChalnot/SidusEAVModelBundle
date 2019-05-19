<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Used to bind a class to a Family, used for property extraction in API Platform for example
 *
 * @Annotation()
 *
 * @Target("CLASS")
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Family
{
    /** @var string */
    public $familyCode;

    /**
     * @param array $config
     *
     * @throws \UnexpectedValueException
     */
    public function __construct($config)
    {
        if (array_key_exists('value', $config)) {
            $this->familyCode = (string) $config['value'];
        }
        if (array_key_exists('familyCode', $config)) {
            $this->familyCode = (string) $config['familyCode'];
        }
        if (!$this->familyCode) {
            throw new \UnexpectedValueException('No familyCode provided for Family annotation');
        }
    }
}
