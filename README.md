# Sidus/EAVModelBundle

A powerful Entity-Attribute-Value (EAV) implementation for Symfony applications, designed to be flexible, extensible, and easy to use.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-8892BF.svg)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.0%20%7C%20%5E8.0-green.svg)](https://symfony.com/)

## Introduction

This bundle allows you to quickly set up a dynamic data model in a Symfony project using Doctrine ORM. Model configuration is done in YAML and everything can be easily extended.

**Key Features:**

- 📝 Model defined in YAML configuration - easy, powerful, and versioned
- 🌍 Data contextualization with custom context axes (language, region, channel, version, etc.)
- ✅ Compatible with Symfony Validator
- 🔍 Flexible query building with EAV-aware query builders
- 🔧 Easily extensible architecture
- 🏗️ Prepared for ORM-agnostic future (Doctrine Bridge included)

## Requirements

- PHP 8.3 or higher
- Symfony 7.0 or 8.0
- Doctrine ORM 3.0+ (for the Doctrine Bridge)

## Installation

Install the bundle using Composer:

```bash
composer require sidus/eav-model-bundle
```

## Quick Start

### 1. Create Your Entities

```php
<?php
// src/Entity/Data.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractData;

#[ORM\Entity]
#[ORM\Table(name: 'eav_data')]
class Data extends AbstractData
{
}
```

```php
<?php
// src/Entity/Value.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractValue;

#[ORM\Entity]
#[ORM\Table(name: 'eav_value')]
class Value extends AbstractValue
{
}
```

### 2. Configure the Bundle

```yaml
# config/packages/sidus_eav_model.yaml
sidus_eav_model:
    data_class: App\Entity\Data
    value_class: App\Entity\Value
    
    families:
        Post:
            attributeAsLabel: title
            attributes:
                title:
                    type: string
                    required: true
                
                content:
                    type: html
                
                publicationDate:
                    type: datetime
                
                author:
                    type: data_selector
                    options:
                        allowed_families:
                            - Author
        
        Author:
            attributeAsLabel: name
            attributes:
                name:
                    type: string
                    required: true
                
                email:
                    type: string
                    validation_rules:
                        - Email: ~
```

### 3. Create the Database Schema

```bash
php bin/console doctrine:schema:update --force
# Or use migrations:
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 4. Use in Your Application

```php
use Sidus\EAVModelBundle\Family\FamilyRegistry;

class PostController
{
    public function __construct(
        private readonly FamilyRegistry $familyRegistry,
    ) {}
    
    public function createPost(): void
    {
        $postFamily = $this->familyRegistry->getFamily('Post');
        $post = $postFamily->createData();
        
        $post->set('title', 'My First Post');
        $post->set('content', '<p>Hello World!</p>');
        $post->set('publicationDate', new \DateTime());
        
        // Save via EntityManager...
    }
}
```

## Documentation

- [Installation Guide](Documentation/01-install.md)
- [Model Configuration](Documentation/02-model.md)
- [Handling Entities](Documentation/04-entities.md)
- [Validation](Documentation/06-validate.md)
- [Querying Data](Documentation/07.1-query.md)
- [Context/Localization](Documentation/09-context.md)
- [Extending the Model](Documentation/11-extend.md)

For full documentation, see the [Documentation](Documentation/) folder.

## Architecture

The bundle is organized into logical namespaces:

```
Sidus\EAVModelBundle\
├── Attribute\          # Attribute types, attributes, registry
├── Bridge\
│   └── Doctrine\       # Doctrine ORM implementation
│       ├── Entity\     # AbstractData, AbstractValue
│       ├── EventListener\
│       ├── Repository\
│       └── Type\
├── Context\            # Context management
├── Data\               # Data/Value interfaces
├── Family\             # Families, registry
├── Exception\          # Exception hierarchy
└── Validator\          # Validation constraints
```

This architecture separates core EAV logic from persistence, preparing for future ORM implementations.

## Upgrading from v1.x

See [UPDATE.md](UPDATE.md) for detailed migration instructions.

## Ecosystem

- [sidus/eav-form-bundle](https://github.com/VincentChalnot/SidusEAVFormBundle) - Form handling for EAV entities
- [sidus/filter-bundle](https://github.com/VincentChalnot/SidusFilterBundle) - Data filtering and search

## What is EAV?

EAV (Entity-Attribute-Value) is a data model where:
- **Entity**: A flexible data container (our "Data" class)
- **Attribute**: A named property defined in configuration (our "Attribute" class)
- **Value**: The actual data stored for an attribute (our "Value" class)

This allows for:
- Dynamic schemas without database migrations
- Multi-value attributes
- Contextualized values (translations, versions, channels)
- Flexible inheritance between data types (families)

## When to Use EAV

✅ **Good use cases:**
- Product Information Management (PIM)
- Content Management Systems
- Flexible form builders
- Multi-tenant applications with custom fields
- Highly dynamic data models

❌ **Not recommended for:**
- Simple CRUD applications
- Performance-critical systems with complex joins
- Data with fixed, well-known schemas

## Contributing

Contributions are welcome! Please read the existing code style and ensure tests pass before submitting a PR.

## License

This bundle is released under the [MIT License](LICENSE).

## Credits

- [Vincent Chalnot](https://github.com/VincentChalnot) - Creator and maintainer
