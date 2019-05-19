<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Doctrine;

use Sidus\EAVModelBundle\Entity\DataInterface;

/**
 * Interface to allow optimized data loader override
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface DataLoaderInterface
{
    /**
     * @param DataInterface[] $entities
     * @param int             $depth
     */
    public function load($entities, $depth = 1);

    /**
     * @param DataInterface|null $entity
     * @param int                $depth
     */
    public function loadSingle(DataInterface $entity = null, $depth = 2);
}
