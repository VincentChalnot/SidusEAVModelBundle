## Questions and Answers

### EAV Manager / Clever Data Manager
Don't mistake this bundle with the [Clever Data Manager](https://github.com/cleverage/eav-manager)!
This bundle is just about the configuration of a "bare" EAV Model without any interface related stuff.
The Clever Data Manager is a full-stack Symfony distribution based on the Sidus/EAVModelBundle aiming to resolve much
higher problems than just the model.

It features:
- Configurable admins, datagrids and CRUD
- Import & export functions
- User management
- Bootstrap UI in SASS

The Clever Data Manager is already in production for several projects but the documentation is still a work in progress
so it's currently not advised to bootstrap new projects with it unless you really know what you're doing.

Also, this bundle is my intellectual property whereas the Clever Data Manager is the property of
[Clever-Age](https://www.clever-age.com) a full-service agency covering the entire digital production chain.

### Performances
See the [performances annex](300-performances.md)

### Cloning/duplicating entities
EAV entities supports the ```clone``` expression. Embed data will be cloned and all other relations will keep their
pointers.

### PHP Classes
Each family can have a specific PHP class for it's entities, but only through Doctrine's single table inheritance.
Use the ```data_class``` option in the family configuration to specify it.

### Default values
Easy, use the ```default``` option in the attribute configuration. It won't work for relations with other entities
though. This can be achieved by overriding the family service and the createData method or by using Doctrine events on
save.

### PHP Version support
It should be compatible with PHP 5.6 and up to 7.2.

### Symfony supports
Symfony 2.7 compatibility has stopped, but we still supports Symfony 2.8 although the target version is clearly
Symfony 3.x.

### API Platform support
Not available for the moment without the use of the [Clever Data Manager](https://github.com/cleverage/eav-manager)
(also see dedicated question)
