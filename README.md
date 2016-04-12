Sidus/EAVModelBundle Documentation
==================================

# Introduction

This documentation is a work in progress

## Demo
If you want to get a quick idea of what this bundle can do, checkout this repository:
https://github.com/VincentChalnot/SidusEAVDemo

## What’s an EAV model
It's basically storing values in a different table than the entities.

Check the confusing and not-so-accurate Wikipedia article:
https://en.wikipedia.org/wiki/Entity%E2%80%93attribute%E2%80%93value_model

## Why using it
- Grouping in the same place the model and the metadata.
- Allowing ultra-fast modelisation because it's super easy to bootstrap.
- Storing contextual values per: locale, channel (web/print/mobile), versions.
- Managing single-value attributes and multiple-values attributes the same way and beeing able to change your mind after without having to do data recovery
- Grouping and ordering attributes
- Easy CRUD: your forms are already configured !

## Why not using it ?
Performances: not a real issue because MySQL is usable for searching in a vast amount of data be it an EAV model or a more standard relationnal model. Solution: Elastic Search; it’s currently optionnaly supported but you have to do a lots of manual configuration over your model, this will be an key feature in a near future.

If you a complex relationnal model and you plan to use a lots of joins to retrieve data, it might be best to keep your relational model outside of the EAV model but both can coexists without any problem.

## The implementation
We are using Doctrine as it’s the most widely supported ORM by the Symfony community and we’re aiming at a MySQL/MariaDB implementation only for data storage.

In any EAV model there are two sides
- The model itself: Families (data types), Attributes and Attribute Types.
- The data: The values and the class that contains the values, called here “Data”.

In some implementation the model is stored in the database but here we chose to maintain the model in Symfony service configuration for several reasons:
- For performance reasons, you always needs to access a lots of components from your model and lazy loading will generate many unnecessary SQL requests. Symfony services are lazy loaded from PHP cache system which is very very fast compared to any other storage system.
- For complexity reason: with services, you can always define new services, use injections, extend existing services and have complex behaviors for your entities.
- A Symfony configuration is easy to write and to maintain and can be versionned, when your model is stored in your database along with your data you will have a very hard time to maintain the same model on your different environments.
- Because the final users *NEVER* edits the model directly in production, it’s always some expert or some developer that does it on a testing environment first and we prefer simple yaml configuration files over a complex administration system that can fail.
- It allows you to add layers of configuration for your special needs, for example you can configure directly some form options in the attribute declaration.
- Finally, you can split your configuration and organise it in different files if needed be and you can comment it, which is a powerful feature when your model starts to grow bigger and bigger with hundreds of different attributes.

Families and attributes are services automatically generated from your configuration, attribute types are standard Symfony services.

## Example
For a basic blog the configuration will look like this:

```yaml
    families:
        post:
            attributeAsLabel: title
            attributes:
                - title
                - content
                - publicationDate
                - publicationStatus
                - author
                - tags
                - isFeatured

        author:
            attributeAsLabel: name
            attributes:
                - name
                - email

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
            form_options:
                family: author

        tags:
            multiple: true
            form_options:
                sortable: true

        isFeatured:
            type: switch

        name:
            required: true

        email:
            validation_rules:
                - Email: ~
```

# Installation
## Specific configuration

# Configuration
## Family configuration reference
## Attributes configuration reference

# Extending the model
## Custom attribute types
## Custom relations
## Form customization

# Going further: the EAV Toolkit