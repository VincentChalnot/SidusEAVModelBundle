---
currentMenu: serialize
---

## Serialization

This bundle is compatible with Symfony's Serializer. To enable the normalization services, activate the serializer
option in the configuration:

````yaml
sidus_eav_model:
    serializer_enabled: true
````

Now the normalizer will add the EAV attributes to the normalized data and supports the denormalization of data
containing the EAV attributes.

You can also normalize families and attributes but obviously not denormalize them because they are not meant to be
modified.

### Custom options

The behavior of the serializer can be controlled a little bit further than the standard Symfony Serializer, it allows
you to describe the way you want your data serialized with options in the model and in the context during serialization.

#### Model options

You can use the ````serializer```` option in your attribute options to tweek the serialization output:

````yaml
sidus_eav_model:
    families:
        Post:
            attributes:
                # ... Other attributes
                author:
                    type: data_selector
                    options:
                        serializer:
                            groups: [ ... ]
                            exposer: true|false
                            # For relations only:
                            by_reference: true|false
                            by_short_reference: true|false
                        # ... Other options
````

- The ````groups```` option behave just like standard serialization groups in conjunction with the ````groups````
context option.

- The ````expose```` option, if set to false will always block the serialization of the attribute.

- ````by_reference```` will only output a limited set of information, check the next paragraph.

- ````by_short_reference```` will only output the identifier of the related entity.

#### Context options

If you haven't read the dedicated chapter about the [EAV context](09-context.md) you might get confused about the
difference between the *EAV* context and the *Serializer* context, which are two completely separate things.

This chapter concerns the *Serializer* context options but you can also pass the *EAV* ````context```` option inside
your *Serializer* context options to declare the current EAV context used to normalize or denormalize the entity:

````php
<?php
/**
 * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer
 * @var \Sidus\EAVModelBundle\Entity\DataInterface $data
 * @var string $format
 */
$normalizedData = $normalizer->normalize(
    $data,
    $format,
    [ // Context
        'context' => [
            'language' => 'en',
            'channel' => 'web',
            'publication' => 'published',
        ],
    ]
);
````

This works the same way for denormalization.

##### Normalization

````php
<?php
/**
 * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer
 * @var \Sidus\EAVModelBundle\Entity\DataInterface $data
 * @var string $format
 */
$normalizedData = $normalizer->normalize(
    $data,
    $format,
    [ // Context
        'by_reference' => true,
    ]
);
````

This will output a limited set of data controlled by this configuration parameter:
````sidus_eav_model.normalizer.data.reference_attributes````

By default it will output the id, identifier, familyCode and label. These information allows any system or human being
to identify the data in a unique way.

You can also choose to extract the short reference, this will only output the identifier property.

````php
<?php
/**
 * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer
 * @var \Sidus\EAVModelBundle\Entity\DataInterface $data
 * @var string $format
 */
$identifier = $normalizer->normalize(
    $data,
    $format,
    [ // Context
        'by_short_reference' => true,
    ]
);
````

##### Denormalization

During denormalization, if the "family" information is not present in the data, you can pass it using the context:

````php
<?php

use Sidus\EAVModelBundle\Entity\DataInterface;

/**
 * @var \Symfony\Component\Serializer\Normalizer\DenormalizerInterface $normalizer
 * @var string $format
 * @var string|\Sidus\EAVModelBundle\Model\FamilyInterface $family
 */
$data1 = $normalizer->denormalize(
    [
        // Can be either of the following:
        'family' => $family,
        'familyCode' => $family,
        'family_code' => $family,
    ],
    DataInterface::class, // Can also be your data class
    $format,
    [ // Context
        // No need to pass the family in the context
    ]
);

$data2 = $normalizer->denormalize(
    [
        // Data without family info
    ],
    DataInterface::class, // Can also be your data class
    $format,
    [ // Context
        'family' => $family,
    ]
);
````

Finally, if you are using single table inheritance for your data class, and have a single family matching your data
class, you can directly use your class and you don't need to provide any family information:

````php
<?php

/**
 * @var \Symfony\Component\Serializer\Normalizer\DenormalizerInterface $normalizer
 * @var string $format
 */
$data1 = $normalizer->denormalize(
    [
        // Data without family info
    ],
    \MyNamespace\EAVModelBundle\Entity\Book::class, // Only if you have a single family matching this data class
    $format,
    [ // Context
        // No need to pass the family in the context
    ]
);
````

See next chapter for more information about this: [Custom classes](12-custom_classes.md)

### Internals

#### Specific configuration

The parameter ````sidus_eav_model.denormalizer.data.ignored_attributes```` lists a number of properties that will
always be ignored when denormalizing data.

For families: ````sidus_eav_model.normalizer.family.ignored_attributes````

And for attributes: ````sidus_eav_model.normalizer.attribute.ignored_attributes````

For ignored properties during normalization, this parameter is used:
````sidus_eav_model.normalizer.data.ignored_attributes````.

#### Entity provider

During denormalization, the system often needs to resolve existing entities based on their family and identifier.

This process is controlled by the ````EntityProvider```` service and always require a family.
The data passed to the service can be:

 - A scalar: The service will try to find the entity by identifier and fallback to the id.
 - An array: The service will check for the id property and then for the identifier property.

This service also keeps tracks of created entities before flush in order to allow associations with entities that have
not been flushed yet.
