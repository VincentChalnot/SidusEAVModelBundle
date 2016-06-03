<?php

namespace Sidus\EAVModelBundle\Event;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use Sidus\EAVModelBundle\Entity\DataInterface;

/**
 * Handle serialization of the EAV model
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DataSerializerHandler implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'method' => 'postSerialize',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event
     */
    public function postSerialize(ObjectEvent $event)
    {
        /** @var DataInterface $data */
        $data = $event->getObject();
        if (!$data instanceof DataInterface) {
            return;
        }
        $context = $event->getContext();
        $visitor = $event->getVisitor();

        $family = $data->getFamily();
        foreach ($family->getAttributes() as $attribute) {
            $propertyMetadata = new VirtualPropertyMetadata(get_class($data), 'get'.ucfirst($attribute->getCode()));
            $context->pushPropertyMetadata($propertyMetadata);
            $visitor->visitProperty($propertyMetadata, $data, $context);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $context->popPropertyMetadata();
        }
    }
}
