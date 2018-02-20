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
 * Any class that holds DQL for the Query Builder must implements this interface
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DQLHandlerInterface
{
    /**
     * @return string
     */
    public function getDQL();

    /**
     * @return array
     */
    public function getParameters();
}
