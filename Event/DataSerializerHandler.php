<?php

namespace Sidus\EAVModelBundle\Event;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use Sidus\EAVModelBundle\Entity\Data;

class DataSerializerHandler implements EventSubscriberInterface
{
    /**
     * @inheritdoc
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
        /** @var Data $data */
        $data = $event->getObject();
        if (!$data instanceof Data) {
            return;
        }
        $context = $event->getContext();
        $visitor = $event->getVisitor();

        $family = $data->getFamily();
        foreach ($family->getAttributes() as $attribute) {
            $propertyMetadata = new VirtualPropertyMetadata(get_class($data), $attribute->getCode());
            $context->pushPropertyMetadata($propertyMetadata);
            $visitor->visitProperty($propertyMetadata, $data, $context);
            $context->popPropertyMetadata();
        }
    }
}
