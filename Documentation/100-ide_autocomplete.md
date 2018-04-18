---
currentMenu: ide_autocomplete
---

#### Intro

You can optionally activate a code generator to create fake abstract classes for your IDE autocomplete.
This means that when coding you can instantly know what are the attributes present in your model and the type of data
they return.

#### Install

In your config_dev.yml, add this line at the beginning:

````yml
imports:
    - { resource: '@SidusEAVModelBundle/Resources/config/services/annotation_generator.yml' }
````

The fake classes should be generated in /var/annotations/Sidus/EAV

Theses classes should __NEVER__ be used for real code, only for annotations as they are not valid
PHP classes, they are only generated in your dev environement and you should not version them.

The annotations directory must not be ignored by your IDE, for example in PHPStorm you should only
manually exclude all directories inside /var without including /var/annotations.

#### Usage:

Use the __@var__ annotation :

````php
/** @var \Sidus\EAV\FamilyCode $var */
$var->getTitle();
````

![Autocomplete Example](assets/autocomplete_example.png)

Note that it will also autocomplete methods presents in the data class you provided for the
family.
