## Events

| Note |
| ---- |
| This feature is only available since v1.2.21 |

Doctrine events can sometimes be fastidious to work with, especially with an EAV model where changes can occur on both
the Data and the Values entities.

In order to ease the creation of custom rules and dynamic attributes, you can use the ````EAVEvent```` system which
works during the ````onFlush```` Doctrine event.

### Register a listener

This feature utilizes the standard Symfony event system:

````yaml
services:
    App\Event\MyListener:
        autowire: true
        autoconfigure: true
        public: false
        tags:
            - { name: kernel.event_listener, event: sidus.eav_data }
````

This also works with subscribers, obviously.

### Create the listener

````php
<?php

namespace App\Event;

use Sidus\EAVModelBundle\Event\EAVEvent;

class MyListener
{
    /**
     * @param EAVEvent $event
     */
    public function onSidusEavData(EAVEvent $event)
    {
        $data = $event->getData();
        
        // Do stuff with $data

        if (EAVEvent::STATE_CREATED === $event->getState()) {
            // Check the state of the entity to add custom state-base logic
        }


        // If you need to update the $data entity, don't flush the EntityManager, use this method:
        $event->recomputeChangeset($data);
    }
}
````

Keep in mind that this event is called inside the Doctrine's onFlush loop which does not tolerate recursive flush really
well, hence the existence of the ````EAVEvent::recomputeChangeset```` method to allow you to force Doctrine to take your
changes into account.

### Computing changesets

The ````EAVEvent```` class has built-in methods to compute the changeset of the EAV entity.

If you need the changeset of non-EAV properties for the Data entity, use this method:

````php
<?php
/** @var \Sidus\EAVModelBundle\Event\EAVEvent $event */
$changeset = $event->getDataChangeset();
````

This will give you a simple Doctrine changeset in the form of an array.

More often, you need the changeset for the EAV values related to the data:

````php
<?php
/** @var \Sidus\EAVModelBundle\Event\EAVEvent $event */
$changeset = $event->getValuesChangeset();
````

This will gives you an array of array of ````ValueChangeset```` indexed first by their attribute code and then by the
Spl object id of the value used internally by Doctrine.

Finally, you can also get the changeset for a specific attribute:

````php
<?php
/**
 * @var \Sidus\EAVModelBundle\Event\EAVEvent $event
 * @var \Sidus\EAVModelBundle\Model\AttributeInterface $attribute
 */
$changeset = $event->getAttributeChangeset($attribute);
````

Which will gives you an array of ````ValueChangeset````.
