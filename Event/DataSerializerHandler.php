<?php

namespace Sidus\EAVModelBundle\Event;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GenericSerializationVisitor;
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
                'event' => Events::PRE_SERIALIZE,
                'method' => 'preSerialize',
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'method' => 'postSerialize',
            ],
        ];
    }

    public function preSerialize(ObjectEvent $event)
    {
        /** @var GenericSerializationVisitor $visitor */
        $visitor = $event->getVisitor();
        $data = $event->getObject();
        if (!$data instanceof Data) {
            return;
        }
        $visitor->addData('family', $data->getFamilyCode());
    }

    /**
     * @param ObjectEvent $event
     * @throws \Exception
     */
    public function postSerialize(ObjectEvent $event)
    {
        /** @var GenericSerializationVisitor $visitor */
        $visitor = $event->getVisitor();
        $data = $event->getObject();
        if (!$data instanceof Data) {
            return;
        }
        $family = $data->getFamily();
        foreach ($family->getAttributes() as $attribute) {
            if ($attribute->isMultiple()) {
                $valueData = $data->getValuesData($attribute);
            } else {
                $valueData = $data->getValueData($attribute);
            }
            $visitor->addData($attribute->getCode(), $valueData);
        }
    }
}
