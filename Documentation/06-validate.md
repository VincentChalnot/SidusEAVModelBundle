## Validation

The EAV model is 100% compatible with Symfony's validator component.

### Validating attributes

There is an existing mechanism that loads automatically validation constraints defined in the model declaration.

````yaml
sidus_eav_model:
    families:
        Author:
            attributeAsLabel: name
            attributes:
                name:
                    required: true # This is equivalent to using the NotBlank validator

                email:
                    validation_rules:
                        - Email:
                            message: "The email '{{ value }}' is not a valid email."
                            checkMX: true
````

You can use any native Symfony constraint or use custom constraints with the same syntax than the traditional YAML
syntax.

See the full [Symfony Validation Constraints Reference](https://symfony.com/doc/current/reference/constraints.html)


### Class constraints validation

Sometimes you want to implements a validation constraint directly on the family and not on a single attribute.

This is currently not supported in the model definition but can be achieved by defining a dedicated class for a given
family. See [12 - Custom classes](12-custom_classes.md)

Simply use the [existing mechanism to declare class constraints in Symfony](https://symfony.com/doc/current/validation/custom_constraint.html#class-constraint-validator)

### Using validation groups

Because we can't declare a validation constraints that matches every validation group in Symfony, we need to declare
every new validation group in the Data class like this:

````php
<?php

namespace MyNamespace\EAVModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Entity\AbstractData;
use Sidus\EAVModelBundle\Validator\Constraints\Data as DataConstraint;

/**
 * @ORM\Table(...)
 * @ORM\Entity(...)
 * 
 * @DataConstraint(groups={"custom_group_1", "custom_group_2", "..."})
 */
class Data extends AbstractData
{
}
````

| WARNING |
| ------- |
| You can't use custom validation groups in attribute constraints definitions without declaring them like this |
