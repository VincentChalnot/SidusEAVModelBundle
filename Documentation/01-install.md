---
currentMenu: install
---

## Installation

This bundle can be installed with a few easy steps.

### Bundle installation

The bundle installation covers four steps:
- Requiring the library with composer
- Enabling the bundle in your kernel,
- Declaring some required classes
- Defining the default minimum configuration.

#### Require the bundle with composer:

````bash
$ composer require sidus/eav-model-bundle "~1.2"
````

#### Add the bundle to AppKernel.php

````php
<?php
/**
 * app/AppKernel.php
 */
class AppKernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Sidus\EAVModelBundle\SidusEAVModelBundle(),
            // ...
        ];
    }
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
 *     @ORM\Index(name="family", columns={"family_code"}),
 *     @ORM\Index(name="string_search", columns={"attribute_code", "string_value"}),
 *     @ORM\Index(name="int_search", columns={"attribute_code", "integer_value"}),
 *     @ORM\Index(name="bool_search", columns={"attribute_code", "bool_value"}),
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

You're now ready to [configure your model](02-model.md)
