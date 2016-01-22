<?php

namespace Sidus\EAVModelBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler;
use Sidus\EAVModelBundle\Entity\Data;

class DataEventSubscriber implements EventSubscriber
{
    /** @var FamilyConfigurationHandler */
    protected $familyConfigurationHandler;

    public function __construct(FamilyConfigurationHandler $familyConfigurationHandler)
    {
        $this->familyConfigurationHandler = $familyConfigurationHandler;
    }

    public function getSubscribedEvents()
    {
        return [
            'postLoad',
        ];
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $this->injectFamily($args);
    }

    public function injectFamily(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof Data) {
            return;
        }
        $entity->setFamily($this->familyConfigurationHandler->getFamily($entity->getFamilyCode()));
    }
}