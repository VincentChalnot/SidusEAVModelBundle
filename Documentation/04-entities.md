## Creating entities
To create a new entity you must first fetch the family you want from configuration.

````php
<?php
/** @var \Sidus\EAVModelBundle\Registry\FamilyRegistry $familyRegistry */
$familyRegistry = $container->get('sidus_eav_model.family.registry');
$postFamily = $familyRegistry->getFamily('Post');

$newPost = $postFamily->createData();
````

### Editing an entity and accessing its values
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
/** @var \Sidus\EAVModelBundle\Entity\DataInterface $newPost */
$newPost->set('title', 'I LOVE SYMFONY');
echo $newPost->get('title');
````

Which is exactly what the magic method will do in the background.

We do not implements the magic getters and setters for properties (\__get, \__set) because they do not allow you to
easily override the business logic in a child class.

Note: In order for the forms to be able to PropertyAccessor (used in forms) to read from magic calls, we enable the
"enableMagicCall" option globally.

#### Persisting or deleting an entity
To persist or delete an entity, you can't flush just the Data entity but you need to do a global flush because values
are stored separately from the entity.

````php
<?php
/** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
$em = $doctrine->getManager();

/** @var \Sidus\EAVModelBundle\Entity\DataInterface $data */
$em->persist($data);
$em->flush();
````
