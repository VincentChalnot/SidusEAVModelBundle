## Data Contextualisation

Sometimes the data you store for a given entity and a given attribute can vary based on various contexts.

For internationalization for example, we have at least the ````language```` context axis and optionally the
````region```` context axis. Both combined they are used to define the locale of the user.

In this example, a given locale ````en_US```` corresponds to the context value ````en```` for the context axis
````language```` and the context value ````US```` for the context axis ````region````.

> **NOTE:**<br>
> We chose not to force any default context axis so you are free to define them yourself. This should allows you to
> really define your own custom context axis based on your business logic instead of being forced to adapt to an
> existent behavior.

You need to define for each attribute the context axis mask that applies to their values, meaning the list of context
axis that makes the attribute's values variable depending of their context values.

**Examples:**
- A label attribute may vary depending on the language but maybe not the region: Does the ````en_US```` title and the
  ````en_UK```` title should always be the same?
- A description however might contains specific variations depending on both the ````language```` and the ````region````
  axis.
- If you have an attribute corresponding to a publication date, it may depends upon the ````region```` axis but
  certainly not upon the ````language```` axis. It may also vary upon a different context axis like a ````market````
  axis.
- You may have products with certain legal attributes that varies based on a ````market```` context axis (Europe,
  America, Asia, etc.)
- Certain attributes might have different values based on your ````channel```` (Web, mobile, print)
- You may need to store the ````source```` from which the value came from and then define later an specific fallback
  rule allowing the attributes contexualized by ````source```` to return different "master" values depending on all
  different source versions.
- You will probably need custom axis corresponding to your business logic that no one knows except you.

> **NOTE**<br>
> There is no system to limit the context axis values to certain values in this bundle, you will have to validate your
> context axis values yourself or just make sure the end user cannot set any value he wants.

### Setup

Like any powerful tool, this feature requires a certain amount of setup but we tried to make it as easy as possible.

#### Base configuration

You must declare a default context in the global config:

````yaml
sidus_eav_model:
    # ...
    default_context:
        language: en
        region: US
````

> **NOTE:**<br>
> You **must** declare all your context axis in the ````default_context```` config and each context axis **must** have
> a default value.

#### Declaring contextual attributes.

By default all you attributes won't be contextualized, this means that they will always return and store the same values
whatever your current context may be.

````yaml
sidus_eav_model:
    families:
        Product:
            attributeAsLabel: title
            attributeAsIdentifier: sku
            attributes:
                sku:
                    type: string_identifier
                    # Simply don't define any context_mask option if you don't want your attribute to be contextualized

                title:
                    # This means the values of this attribute will vary depending on the language
                    context_mask: [language]

                description:
                    type: text
                    # This means the values of this attribute will vary depending on the language AND the region
                    context_mask: [language, region]

                legal:
                    type: text
                    context_mask: [market]

                # ... Other attributes
````

#### Entities setup

Your ````Value```` class must be able to store you different context values. This is a simple example with only the
````language```` and the ````region```` context axis.

````php
<?php

namespace MyNamespace\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractValue;

/**
 * @see install procedure for doctrine annotations
 */
class Value extends AbstractValue
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=3 nullable=true)
     */
    protected $language;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    protected $region;

    /**
     * @return array
     */
    public static function getContextKeys()
    {
        return ['language', 'region'];
    }
}
````

If the Value::getContextKeys() fails to return the same context axis than the ones in the default context, you will get
an exception asking you to fix this.

> **NOTE:**<br>
> Each context axis must be nullable unless ALL your attributes are contextualized by this data axis.

You will be able to access the data associated to this context in several ways:

#### Setting the context globally

This will set the context for the entire application, it will also try to store the context in the session.
````php
<?php
/** @var \Sidus\EAVModelBundle\Context\ContextManager $contextManager */
$contextManager->setContext([
    'language' => 'en',
    'region' => 'US',
]);
````

#### Setting the context by data
````php
<?php
/** @var \Sidus\EAVModelBundle\Entity\ContextualDataInterface $data */
$data->setCurrentContext([
    'language' => 'en',
    'region' => 'US',
]);
````

#### Getting a value for a specific context
````php
<?php
/** @var \Sidus\EAVModelBundle\Entity\DataInterface $data */
$data->get(
    'title',
    [
        'language' => 'en',
        'region' => 'US',
    ]
);
// OR
$data->getTitle([
    'language' => 'en',
    'region' => 'US',
]);
````

#### Setting a value for a specific context
````php
<?php
/** @var \Sidus\EAVModelBundle\Entity\DataInterface $data */
$data->set(
    'title',
    'My Little Tauntaun',
    [
        'language' => 'en',
        'region' => 'US',
    ]
);
// OR
$data->setTitle(
    'My Little Tauntaun',
    [
        'language' => 'en',
        'region' => 'US',
    ]
);
````

> **NOTE**:<br>
> You don't need to pass the entire context all the time, it will simply be merged with your current context to make
> sure you have all your context axis set.
