## How to query data

### Fetching the repository

````php
<?php
/**
 * @var \Sidus\EAVModelBundle\Model\FamilyInterface $family
 * @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
 * @var \CleverAge\EAVManager\EAVModelBundle\Entity\DataRepository $dataRepository
 * @var integer $id
*/
$dataRepository = $doctrine->getRepository($family->getDataClass());

$dataRepository->find($id);
````

## EAVQueryBuilder

Sometimes you want to programmatically search for entities in your database.
When using a traditional relational model in Doctrine you can query your database
with the QueryBuilder and the DQL language.
When using an EAV model, you need to make a join on the values table each time you
put a condition on the value of an attribute and the resulting queries are really
complicated to write and to maintain manually.

Introducing the EAVQueryBuilder:

````php
<?php
/**
 * @var \Sidus\EAVModelBundle\Entity\DataRepository $dataRepository
 * @var \Sidus\EAVModelBundle\Model\FamilyInterface $categoryFamily
 */

// Initializing a new EAVQueryBuilder from the Category family
$eavQb = $dataRepository->createEAVQueryBuilder($categoryFamily);

// Creating the proper DQL and parameters to match some category codes
$dqlHandler = $eavQb->a('categoryCode')->in([
    'books',
    'comics',
]);

// Apply dql to main query builder
$qb = $eavQb->apply($dqlHandler);

// Fetching result, the traditional Doctrine's way
$categories = $qb->getQuery()->getResult();
````

So basically, we need the family and the data repository to create the EAV
query builder instance, like a classic query builder.

We use the syntax :
```php
$eavQb->a({{ATTRIBUTE_CODE}})->{{OPERATOR}}({{VALUE}});
```
To generate an DQLHandler instance that contains the DQL and the parameters that
will be used later to build the doctrine query builder.

In the end, we apply the DQLHandler to the main EAVQueryBuilder instance to fetch
the Doctrine query builder.

In a more complex example, you can create imbricated DQLHandlers with AND and OR
conditions:

````php
<?php
/**
 * @var \Sidus\EAVModelBundle\Entity\DataRepository $dataRepository
 * @var \Sidus\EAVModelBundle\Model\FamilyInterface $bookFamily
 * @var \Sidus\EAVModelBundle\Entity\DataInterface[] $categories
 */
$eavQb = $dataRepository->createEAVQueryBuilder($bookFamily);

$eavQb->addOrderBy($eavQb->a('title'), 'DESC');

$qb = $eavQb->apply($eavQb->getOr([
    $eavQb->getAnd([
        $eavQb->a('publicationStatus')->in(['validated', 'published']),
        $eavQb->a('price')->lte(16),
        $eavQb->a('title')->like('%programming%'),
    ]),
    $eavQb->getAnd([
        $eavQb->a('title')->like('%example%'),
        $eavQb->a('categories')->in($categories), // Fetched earlier
        $eavQb->a('tomeNumber')->between(2, 4),
    ]),
]));

$books = $qb->getQuery()->getResult();
````

The end result would look like this in relational SQL:

````sql
SELECT * FROM Book b WHERE (
    (
        b.publicationStatus IN ('validated', 'published') AND
        b.price <= 16 AND
        b.title LIKE '%programming%'
    )
    OR
    (
        b.title LIKE '%example%' AND
        b.categoried IN (37, 42) AND
        b.tomeNumber BETWEEN 2 AND 4
    )
)
ORDER BY b.title DESC
````
