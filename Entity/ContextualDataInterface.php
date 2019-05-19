<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Entity;

/**
 * All data must implements this class that defines how context information is handled
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextualDataInterface extends DataInterface
{
    /**
     * @return array
     */
    public function getCurrentContext();

    /**
     * @param array $currentContext
     */
    public function setCurrentContext(array $currentContext = []);
}
