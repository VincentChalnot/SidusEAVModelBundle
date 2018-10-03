<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Sidus\EAVModelBundle\Entity\DataInterface;
use Sidus\EAVModelBundle\Entity\ValueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Listens to Doctrine events and convert them to EAVEvents
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineToEAVEventConverter implements EventSubscriber
{
    /** @var EAVEvent[] */
    protected $eavEvents;

    /** @var ValueInterface[][] */
    protected $changedValues = [];

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->eavEvents = new \SplObjectStorage();
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $entitiesByState = [
            EAVEvent::STATE_CREATED => $uow->getScheduledEntityInsertions(),
            EAVEvent::STATE_UPDATED => $uow->getScheduledEntityUpdates(),
            EAVEvent::STATE_REMOVED => $uow->getScheduledEntityDeletions(),
        ];

        foreach ($entitiesByState as $state => $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof DataInterface) {
                    $this->processData($args->getEntityManager(), $entity, $state);
                }
                if ($entity instanceof ValueInterface) {
                    $this->processValue($entity, $state);
                }
            }
        }

        foreach ($this->changedValues as $state => $changedValues) {
            foreach ($changedValues as $changedValue) {
                $data = $changedValue->getData();
                if ($this->eavEvents->offsetExists($data)) {
                    $eavEvent = $this->eavEvents->offsetGet($data);
                } else {
                    $eavEvent = new EAVEvent($args->getEntityManager(), $data, EAVEvent::STATE_UPDATED);
                    $this->eavEvents->offsetSet($data, $eavEvent);
                }
                $eavEvent->addChangedValue($changedValue, $state);
            }
        }
        $this->changedValues = [];

        while ($this->eavEvents->count() > 0) {
            $this->eavEvents->rewind();
            $data = $this->eavEvents->current();
            $eavEvent = $this->eavEvents->offsetGet($data);
            $this->eavEvents->offsetUnset($data);
            $this->eventDispatcher->dispatch('sidus.eav_data', $eavEvent);
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param DataInterface $data
     * @param int           $state
     */
    protected function processData(EntityManager $entityManager, DataInterface $data, $state)
    {
        if ($this->eavEvents->offsetExists($data)) {
            throw new \UnexpectedValueException("Duplicate event for data {$data->getId()}");
        }
        $this->eavEvents->offsetSet($data, new EAVEvent($entityManager, $data, $state));
    }

    /**
     * @param ValueInterface $value
     * @param int            $state
     */
    protected function processValue(ValueInterface $value, $state)
    {
        $this->changedValues[$state][] = $value;
    }
}
