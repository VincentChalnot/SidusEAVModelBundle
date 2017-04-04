Sidus/EAVModelBundle Documentation
==================================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/621ec123-268d-4a6b-a1d0-03a1e4e84b48/big.png)](https://insight.sensiolabs.com/projects/621ec123-268d-4a6b-a1d0-03a1e4e84b48)

## Introduction
This bundle allows you to quickly set up a dynamic model in a Symfony project using Doctrine.  
Model configuration is done in Yaml and everything can be easily extended.

### Demo
If you want to get a quick idea of what this bundle can do, checkout the [Demo Symfony Project](https://github.com/VincentChalnot/SidusEAVDemo)

### What’s an EAV model
EAV stands for Entity-Attribute-Value

The main feature consists in storing values in a different table than the entities. Check the confusing and not-so-accurate [Wikipedia article](https://en.wikipedia.org/wiki/Entity%E2%80%93attribute%E2%80%93value_model)

This implementation is actually more an E(A)V model than en traditional EAV model because attributes are not stored in the database but in YAML files.

If you're not familiar with the key concepts of the EAV model, please read the following.

### Why using it
- Grouping in the same place the model and the metadata.
- Allowing ultra-fast model design because it's super easy to bootstrap.
- Storing contextual values per: locale, channel (web/print/mobile), versions.
- Managing single-value attributes and multiple-values attributes the same way and being able to change your mind after without having to do data recovery
- Grouping and ordering attributes
- Easy CRUD: your forms are already configured !

### Why not using it ?
Performances: not a real issue because MySQL is not usable for searching in a vast amount of data anyway, be it an EAV model or a more standard relational model. Solution: Elastic Search: it’s currently optionally supported but you have to do a lots of manual configuration over your model, this will be an key feature in a near future.

If you a have a complex relational model and you plan to use a lots of joins to retrieve data, it might be best to keep your relational model outside of the EAV model but both can coexists without any problem.

### The implementation
We are using Doctrine as it’s the most widely supported ORM by the Symfony community and we’re aiming at a MySQL/MariaDB implementation only for data storage.

In any EAV model there are two sides
- The model itself: Families (data types), Attributes and Attribute Types.
- The data: The values and the class that contains the values, called here “Data”.

In some implementation the model is stored in the database but here we chose to maintain the model in Symfony service configuration for several reasons:
- For performance reasons, you always needs to access a lots of components from your model and lazy loading will generate many unnecessary SQL requests. Symfony services are lazy loaded from PHP cache system which is very very fast compared to any other storage system.
- For complexity reason: with services, you can always define new services, use injections, extend existing services and have complex behaviors for your entities.
- A Symfony configuration is easy to write and to maintain and can be versioned, when your model is stored in your database along with your data you will have a very hard time to maintain the same model on your different environments.
- Because the final users *NEVER* edits the model directly in production, it’s always some expert or some developer that does it on a testing environment first and we prefer simple yaml configuration files over a complex administration system that can fail.
- It allows you to add layers of configuration for your special needs, for example you can configure directly some form options in the attribute declaration.
- Finally, you can split your configuration and organise it in different files if needed be and you can comment it, which is a powerful feature when your model starts to grow bigger and bigger with hundreds of different attributes.

Families and attributes are services automatically generated from your configuration, attribute types are standard Symfony services.

### Example
For a basic blog the configuration will look like this:

````yaml
    families:
        Post:
            attributeAsLabel: title
            attributes:
                title: # Default type is string
                    required: true

                content:
                    type: html
                    group: content

                publicationDate:
                    type: datetime

                publicationStatus:
                    type: choice
                    form_options:
                        choices:
                            draft: Draft
                            published: Published
                            archived: Archived

                author:
                    type: data_selector
                    options:
                        allowed_families: Author

                tags:
                    multiple: true
                    form_options:
                        collection_options:
                            sortable: true

                isFeatured:
                    type: switch

        Author:
            attributeAsLabel: name
            attributes:
                name:
                    required: true

                email:
                    validation_rules:
                        - Email: ~
````

Note that by convention we declare the families in UpperCamelCase and the attributes as lowerCamelCase and we encourage you to do so.

## Installation
This bundle can be installed with a few easy steps. Note that it's highly encouraged to install the full [EAV-Toolkit](https://github.com/VincentChalnot/SidusEAVToolkit) for a better experience. (but you can install it in a second time if you want)

### Bundle installation
The bundle installation covers three steps: requiring the library, enabling the bundle in your kernel, overriding some classes and defining the default minimum configuration.

#### Require the bundle with composer:

````bash
$ composer require sidus/eav-model-bundle "~1.0"
````

#### Add the bundle to AppKernel.php

````php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Sidus\EAVModelBundle\SidusEAVModelBundle(),
        // ...
    );
}
````

#### Create your Data and Value classes

In a dedicated bundle or in one of your bundle (it's generally considered as a good practise to separate your model in
a dedicated bundle), create two new Doctrine entities:

````php
<?php

namespace MyNamespace\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractData;

/**
 * @ORM\Table(name="mynamespace_data", indexes={
 *     @ORM\Index(name="family", columns={"family_code"}),
 *     @ORM\Index(name="updated_at", columns={"updated_at"}),
 *     @ORM\Index(name="created_at", columns={"created_at"})
 * })
 * @ORM\Entity(repositoryClass="Sidus\EAVModelBundle\Entity\DataRepository")
 */
class Data extends AbstractData
{
}
````

````php
<?php

namespace MyNamespace\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractValue;

/**
 * @ORM\Table(name="mynamespace_value", indexes={
 *     @ORM\Index(name="attribute", columns={"attribute_code"}),
 *     @ORM\Index(name="string_search", columns={"attribute_code", "string_value"}),
 *     @ORM\Index(name="int_search", columns={"attribute_code", "integer_value"}),
 *     @ORM\Index(name="position", columns={"position"})
 * })
 * @ORM\Entity(repositoryClass="Sidus\EAVModelBundle\Entity\ValueRepository")
 */
class Value extends AbstractValue
{
}
````

Note that you're in charge of defining the mysql indexes of theses two classes, the indexes provided in the example are
not mandatory but strongly advised for performances.

Single table inheritance can be configured to allow different classes for different families.

#### Base configuration

You will need at least the following configuration:

````yaml
sidus_eav_model:
    data_class: MyNamespace\EAVModelBundle\Entity\Data
    value_class: MyNamespace\EAVModelBundle\Entity\Value
````

This will declare the classes used by the bundle to instantiate EAV data.

## Configuration
At this point your application should run although you won't be able to do anything without defining first your model configuration.

### Model configuration
Please read the example in the first chapter to familiar yourself with the key features of the configuration.

If you want to test your configuration against an existing app, you can do it in the [Demo Symfony Project](https://github.com/VincentChalnot/SidusEAVDemo)

#### Family configuration reference
The families of your model are what would be your classes in a relational model, we call them families instead of classes because they do not necessarily correspond to any PHP class in a strict sens. They are "data types" but such a denomination could lead to many mistakes so we prefer to call them "families".

Each family must define at least an attribute and an attribute as label:
- The list of attribute is a simple array of attribute codes and the order in which you declare them will define the order in which they appear in their edition form.
- The attribute as label defines which attribute should be used to display the object when calling __toString on it.

Just like a standard relational model you can define an inheritance between your families and add attributes to a child family.

Here is a full configuration reference, the <> syntax defines a placeholder:

````yaml
sidus_eav_model:
    families:
        <familyCode>:
            label: <human-readable name of the family> # Use the translator instead of this
            attributeAsLabel: <attributeCode> # Required, except if the family is inherited
            attributeAsIdentifier: <attributeCode> # Can be used to set a virtual primary key on your family
            instantiable: <boolean> # Default true, can be used to define an "abstract" family
            singleton: <boolean> # If true, the family will have only one instance accessible through DataRepository::getInstance
            parent: <familyCode> # When specified, the family will inherits its configuration
            data_class: <PhpClass> # Can be used with single table inheritance to declare specific business logic in a dedicated class
            options: <array> # generic options that can be used by business logic/external libraries
            attributes: # Required
                <attributeCode>: ~ # When using a globally defined attribute
                <attributeCode>: <AttributeConfiguration> # When declaring an attribute locally or overriding a globally defined one
                # ...
````

#### Attributes configuration reference
The attributes (or properties) are defined independently from their families because they will often be reused in many families.

An attribute will define the way a value is edited and stored in the database, each attribute has a unique code and a type (see next chapter).

If you change the code of an attribute, all its previous values stored in the database will be discarded on next save.

Attributes can have a group, mainly to facilitate their edition but also to group them by business logic in order to define permissions (not covered by the current bundle). You can safely change the group of an attribute without having any effect on the way data is stored.

The full configuration reference will help you see what can be done with attributes:

````yaml
sidus_eav_model:
    attributes:
        <attributeCode>:
            type: <attributeTypeCode> # Default "string", see following chapter
            group: <groupCode> # Default null
            options: <array> # Some attribute types require specific options here, example:
                allowed_families: <familyCode[]> # Only for relations/embed: selects the allowed targets families
                hidden: <boolean> # If true attribute will never appear in auto-generated forms
                serializer:
                    by_short_reference: <boolean> # Used with relations, serializer will output only the identifier
                # You can also use the options to pass any custom parameter to the attribute and use them in your code
            form_options: <array> # Standard symfony form options
            form_type: <FormType> # Overrides the form type of the attribute type
            default: <mixed> # Default value, not supported for relations for the moment
            validation_rules: <array> # Standard Symfony validation rules
            required: <boolean> # Default false, empty() PHP function is used for validation
            unique: <boolean> # Default false, check if attribute is unique globally
            multiple: <boolean> # Default false, see following chapter
            collection: <boolean> # Default null, see following chapter
            context_mask: <array> # See dedicated chapter
````

Some codes are reserved like: id, parent, children, values, valueData, createdAt, updatedAt, currentVersion, family and
currentContext. If you use any of these words as attribute codes your application behavior will depends of how you try
to access the entities' data. Don't do that.

##### Attribute types
Attribute types define a common way of editing and storing data, this bundle provides the following types:
- string: Stored as varchar(255), edited as text input
- text: Stored as text, edited as textarea
- integer: Stored as integer, edited as text input with validation
- decimal: Stored as float, edited as text input with validation
- boolean: Stored as boolean, edited as checkbox
- date: Stored as date, edited as Symfony date widget
- datetime: Stored as datetime, edited as Symfony datetime widget
- choice: Stored as varchar(255), edited as choice widget (required "choices" form_options)
- data_selector: Stored in a real Doctrine Many-To-One relationship with a related Data object, edited as a choice
widget. Accepts a list of allowed families in the 'allowed_families' option.
- embed: Stored like data but embed the edition of the foreign entity directly into the form. Requires a single family
in the 'allowed_families' option.
- hidden: Stored as varchar(255), will be present in the form as a hidden input.
- string_identifier: Same as a string but unique and required
- integer_identifier: Same as an integer but unique and required

Additional attribute types can be found in the sidus/eav-bootstrap-bundle:
- html: Stored as text, edited as TinyMCE WYSIWYG editor, featuring full control over configuration
- switch: Stored as boolean, edited as a nice checkbox
- autocomplete_data: Stored like data, edited as an auto-complete input, requires the "family" form_option.
- combo_selector: Allow selection of the family first, then autocomplete of the data, using "autocomplete_data".
Date and datetime are also improved with bootstrap date/time picker.

The only current limitation of the embed type is that you cannot embed a family inside the same family, this creates an
infinite loop during form building.

If you change the type of an attribute, its values will probably be discarded during the next save but it can also leads
to unexpected behaviors: don't change the type of an attribute, create a new one (and remove the previous one if need
be).
The only safe thing you can do is switch between different attributes that stores their data the same way: For example:
data, embed and autocomplete_data are safely interchangeable.


#### Multiple & collection option
The "collection" option allows you to add multiple values for the same attribute and because of the way the EAV model
works, all attribute types are compatible with this option.

The "collection" option defines the way the model works, not the form ! If you want to automatically generate a Symfony
collection of form widgets to edit this attribute you need to use the "multiple" option.

Basically, the multiple option automatically enables the "collection" option but provides in the same time a way to
automatically generate compatible form types to edit your attribute.

This option will probably not behave well in forms without the bootstrap-collection extension of the
sidus/eav-bootstrap-bundle:

https://github.com/VincentChalnot/SidusEAVBootstrapBundle

You can safely switch a single-value attribute to multiple, the current values will be kept as the first value of the
collection. If you switch from an multiple attribute back to a single-valued one, only the first value of the collection
will be kept during the next save of the entity.

When using the "required" option, the collection will pass validation if containing at least one element (event if the
element itself is empty).

When using only the collection option (with multiple: false by default), your form type has to provide a way to edit an
array of values. For example the following configuration is perfectly correct:

````yaml
sidus_eav_model:
    attributes:
        checkboxesExample:
            type: choice # Store data as a string
            collection: true # This means the model is waiting for an array of values, so an array of strings
            form_options:
                multiple: true # This trigger the ability of the ChoiceType to provide an array of values
                expanded: true # Just to have checkboxes instead of an ugly multiselect in the rendering
                choices:
                    bar: foo
                    42: life
````

If you were to use the "multiple" option in the following example the form would not render as expected and saving data
would result in an exception.

An attribute configured to be multiple but not a collection doesn't make any sense and will trigger an exception during
the compilation of the model.

### WARNING
When using the multiple option, the form will automatically generate a collection form type. In order to allow to switch
the attribute from multiple to not multiple, the standard options for the collection have been exchanged with the
'entry_options' option, meaning that if you want to pass options to the collection you will have to use the
'collection_options' option. The DataType will automatically provide the proper options for the form type and remove the
'collection_options' if the attribute is not multiple.

## Basic CRUD
From there you are already ready to use your model in your application, you can do basically three things with your
entities:
- Create an entity from a family
- Editing an entity and accessing its values
- Using a form
- Persisting or deleting an entity

### Creating entities
To create a new entity you must first fetch the family you want from configuration.

````php
<?php
/** @var \Sidus\EAVModelBundle\Configuration\FamilyConfigurationHandler $familyConfigurationHandler */
$familyConfigurationHandler = $container->get('sidus_eav_model.family_configuration.handler');
$postFamily = $familyConfigurationHandler->getFamily('Post');

$newPost = $postFamily->createData();
````

#### Editing an entity and accessing its values
You can simply set or get values of an entity manually in PHP like this:

````php
<?php
$newPost->setTitle('I LOVE SYMFONY');
echo $newPost->getTitle();
````

Yes, this relies on magic methods to work but magic methods are not evil (while code generation definitely is). You can
read sometimes that they are bad in terms of performances but this is less and less true with recent versions of PHP.
The only drawback of using them is the lack of annotations that makes them appearing as errors in your IDE which is not
cool. There is no simple solution for this but we might explore the benefits of automatically generating annotations in
Symfony's cache to allow you to identify them with @var.

UPDATE : You can now generate automatically fake classes from your EAV Model by adding this configuration in
config_dev.yml:
````yml
imports:
    - { resource: '@SidusEAVModelBundle/Resources/config/annotation_generator.yml' }
````
It is VERY important to never actually use the generated classes apart from annotations because they wont work.
All classes will be in the namespace \Sidus\EAV and will be named after your families.
Now you can do this and get autocomplete working in your IDE:

````php
<?php
/** @var \Sidus\EAV\Post $newPost */
$newPost->setTitle('I LOVE SYMFONY');
echo $newPost->getTitle();
````

Or you can also use the alternative syntax:

````php
<?php
$newPost->set('title', 'I LOVE SYMFONY');
echo $newPost->get('title');
````

Which is exactly what the magic method will do in the background.

We do not implements the magic getters and setters for properties (\__get, \__set) because they do not allow you to
easily override the business logic in a child class.

Note: In order for the forms to be able to PropertyAccessor (used in forms) to read from magic calls, we enable the
"enableMagicCall" option globally.

#### Using a form
The default form to edit entities is referenced as 'Sidus\EAVModelBundle\Form\Type\DataType' and the only thing to keep
in mind is that it can't work without an entity or the "family" option.
Alternatively, you can use the 'Sidus\EAVModelBundle\Form\Type\GroupedDataType' form to separate attribute in different
fieldsets for different groups.

#### Persisting or deleting an entity
To persist or delete an entity, you can't flush just the Data entity but you need to do a global flush because values
are stored separately from the entity.

## Extending the model
The existing model allows you to store all the basic kinds of scalar (text, number, dates) and relations to other
families.
However, you might need to store different kind of values in your model.

### Custom attribute types
Attribute types are the link between the user interface and your model, there are many scenarios where you will need
to override existing attribute types or create new ones.
If you take a look at the base Value class, you will notice that it contains a lot of properties suffixed by "Value",
these properties are used to store all the different kind of PHP values and correspond to existing Doctrine types:

The scalar types:

- string (type="string", length=255)
- text (type="text")
- bool (type="boolean")
- integer (type="integer")
- decimal (type="float")
- date (type="date")
- datetime (type="datetime")

There is also a "dataValue" which correspond to a Doctrine's association to an other entity (the actual entity is stored
in the "data")

These properties are storage properties and can be reused for multiple attribute types and attributes.

#### Adding new attribute types using existing storage properties
To create a new attribute type base on one of the properties described earlier, just create a new service.
The first parameter is the attribute type code, the second one is the name of the property used to store the value in
the Value class and the third parameter is the form type.

````yaml
services:
    my_namespace.attribute_type.my_attribute:
        class: '%sidus_eav_model.attribute_type.default.class%'
        arguments: [<my_attribute_code>, <storage_property>, <my_namespace_form_type>]
        tags:
            - { name: sidus.attribute_type }
````

Don't forget to tag your service properly.

#### Overriding existing attribute types
If you need to override an existing attribute type, you can use the following method.
Attribute types are standard tagged symfony services, you should'nt need to override the default class and you will see
that all existing attribute types are based on the same class.
For example if you need to override the form_type of the "html" type:

````yaml
services:
    sidus_eav_model.attribute_type.html:
        class: '%sidus_eav_model.attribute_type.default.class%'
        arguments: [html, textValue, <mynamespace_form_type>]
        tags:
            - { name: sidus.attribute_type }
````

You have to re-declare the whole attribute's type services and you can override more parameters if need be.

#### Custom relations
This chapter covers how to add a new relation to an other Doctrine entity in the EAV model.

First, add the relation in your Value class:

````php
<?php

namespace MyNamespace\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractValue;
use MyNamespace\CustomBundle\Entity\Document;

/**
 * @ORM\Table(name="mynamespace_value")
 * @ORM\Entity(repositoryClass="Sidus\EAVModelBundle\Entity\ValueRepository")
 */
class Value extends AbstractValue
{
    /**
     * @var Document
     * @ORM\ManyToOne(targetEntity="MyNamespace\CustomBundle\Entity\Document", cascade={"persist"})
     * @ORM\JoinColumn(name="document_value_id", referencedColumnName="id", onDelete="cascade", nullable=true)
     */
    protected $documentValue;
    
    /**
     * @return Document
     */
    public function getDocumentValue()
    {
        return $this->documentValue;
    }

    /**
     * @param Document $documentValue
     */
    public function setDocumentValue(Document $documentValue = null)
    {
        $this->documentValue = $documentValue;
    }
}
````

Then declare at least an attribute type using a custom form type:

````yaml
services:
    my_namespace.attribute_type.document:
        class: '%sidus_eav_model.attribute_type.relation.class%'
        arguments: [document, documentValue, <my_namespace_form_type>]
        tags:
            - { name: sidus.attribute_type }
````

You can start using your new attribute type right away in your model configuration.

#### Extra customizations
Regarding the existing AttributeType class, there are a few more things you can do:

The fourth parameter of the attribute's types services can be used to set default form options for the form types.

Additionally, there is a few other options you might want to set using the "calls" options in the service definition

- setEmbedded: If the edition of your value's data is embedded, set this value to true, this will also cascade the
validation of the related value in the current form.
- setRelation: By default it will be set to true if the storage property is not listed in the default storage properties
(excepted "dataValue"). You should not have to concern yourself with this but you can use it.

## Going further: the EAV Toolkit
To build a typical full-scale web application using a dynamic model like this you generally needs a lot more feature
than just a configurable model.

- Admin configuration
- Datagrids & filters
- Upload and asset manager
- Easy Twitter's Bootstrap templating
- Import/export data connectors

Checkout the Toolkit demo:
https://github.com/VincentChalnot/SidusEAVDemo
