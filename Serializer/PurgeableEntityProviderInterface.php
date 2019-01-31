<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Serializer;

use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Allows the entity provider to keep an internal cache of created entities that have not yet been flushed
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface PurgeableEntityProviderInterface extends EntityProviderInterface
{
    /**
     * Purge internal cache for created entities
     */
    public function purgeCreatedEntities();

    /**
     * When an entity is created, we don't need to keep the reference anymore
     *
     * @param OnFlushEventArgs $event
     *
     * @throws \Sidus\EAVModelBundle\Exception\InvalidValueDataException
     */
    public function onFlush(OnFlushEventArgs $event);
}
