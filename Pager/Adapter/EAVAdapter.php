<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Pager\Adapter;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Sidus\EAVModelBundle\Doctrine\DataLoaderInterface;

/**
 * Optimize the loading of multiple data at once
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class EAVAdapter extends DoctrineORMAdapter
{
    /** @var DataLoaderInterface */
    protected $dataLoader;

    /**
     * @param DataLoaderInterface                            $dataLoader
     * @param \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder $query               A Doctrine ORM query or query
     *                                                                            builder.
     * @param Boolean                                        $fetchJoinCollection Whether the query joins a collection
     *                                                                            (true by default).
     * @param Boolean|null                                   $useOutputWalkers    Whether to use output walkers
     *                                                                            pagination mode
     */
    public function __construct(
        DataLoaderInterface $dataLoader,
        $query,
        $fetchJoinCollection = false,
        $useOutputWalkers = null
    ) {
        $this->dataLoader = $dataLoader;
        parent::__construct($query, $fetchJoinCollection, $useOutputWalkers);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $iterator = parent::getSlice($offset, $length);
        $this->dataLoader->load($iterator);

        return $iterator;
    }
}
