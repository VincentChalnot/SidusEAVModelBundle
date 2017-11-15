## Custom classes

The default behavior for EAV Data entities hydration in Doctrine is to use a single class.
This is the "Data" class you need to create and configure at the installation of this bundle.

However, there is an alternative behavior you can setup by using Doctrine's single table inheritance,
using this feature you can actually have a different class for each Family in the EAV model.

> **NOTE**:
> This feature is mandatory when using certain external bundles that requires a different class for each different
> model, like SonataAdmin, ApiPlatform, etc.

### Setup your Data class

Add the following two annotations to your Data class:

````phpdoc
@ORM\InheritanceType("SINGLE_TABLE")
@ORM\DiscriminatorColumn(name="discr", type="string")
````

So your class should looks like this:

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
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
class Data extends AbstractData
{
}
````

Because of the way the EAV model works, it is not possible to use different entities mapped to different tables on the
database side. That's why you need to use single table inheritance.
(It would probably also works with joined table inheritance but who uses that anyway?)

#### Update your schema

If you have already created your schema, you need to update it because Doctrine will add the discriminator column to
it in order for the inheritance to work.

### Create your custom class

Create the class that will be used by your family.
By convention you should name it the same way than your family.

Note that it is possible but not advised to use the same custom data class for multiple families.

````php
<?php

namespace MyNamespace\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Sidus\EAVModelBundle\Entity\DataRepository")
 */
class MyFamily extends Data
{
}
````

### Configure your family

````yaml
sidus_eav_model:
    families:
        MyFamily:
            data_class: MyNamespace\EAVModelBundle\Entity\MyFamily
            # ...
````

#### Update your existing data

If you already had data for this family stored in your database, the discriminator column needs to be updated,
you can use the following command to do this:

````bash
$ bin/console sidus:data:fix-discriminator
````
