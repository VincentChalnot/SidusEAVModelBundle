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
