<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Event;

use Sidus\EAVModelBundle\Entity\ValueInterface;

/**
 * This class represents a the changeset of a value
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ValueChangeset
{
    /** @var ValueInterface */
    protected $value;

    /** @var mixed */
    protected $previousValue;

    /** @var int */
    protected $state;

    /**
     * @param ValueInterface $value
     * @param mixed          $previousValue
     * @param int            $state
     */
    public function __construct(ValueInterface $value, $previousValue, $state)
    {
        $this->value = $value;
        $this->previousValue = $previousValue;
        $this->state = $state;
    }

    /**
     * @return ValueInterface
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getPreviousValue()
    {
        return $this->previousValue;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }
}
