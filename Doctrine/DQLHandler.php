<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Doctrine;

/**
 * DQL and parameter holder during query resolution
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DQLHandler implements DQLHandlerInterface
{
    /** @var string */
    protected $dql;

    /** @var array */
    protected $parameters = [];

    /**
     * @param string $dql
     * @param array  $parameters
     */
    public function __construct($dql, array $parameters = [])
    {
        $this->dql = $dql;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getDQL()
    {
        return $this->dql;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
