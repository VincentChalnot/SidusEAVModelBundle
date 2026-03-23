## Installation

This guide covers installing and setting up the Sidus EAV Model Bundle.

> **Version 2.0**: This documentation is for version 2.0+, which requires PHP 8.3+, Symfony 7.0/8.0, and Doctrine ORM 3.0+. For older versions, see the v1.3 branch documentation.

### Requirements

- PHP 8.3 or higher
- Symfony 7.0 or 8.0
- Doctrine ORM 3.0+ (optional, but required for the Doctrine Bridge)

### Step 1: Install via Composer

```bash
composer require sidus/eav-model-bundle "^2.0"
```

### Step 2: Create Your Entity Classes

Create two entities that extend the abstract base classes. These will store your EAV data.

#### Data Entity

```php
<?php
// src/Entity/Data.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractData;

#[ORM\Entity]
#[ORM\Table(name: 'eav_data')]
#[ORM\Index(name: 'family_idx', columns: ['family_code'])]
#[ORM\Index(name: 'updated_at_idx', columns: ['updated_at'])]
#[ORM\Index(name: 'created_at_idx', columns: ['created_at'])]
class Data extends AbstractData
{
    // Add any custom properties or methods here
}
```

#### Value Entity

```php
<?php
// src/Entity/Value.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractValue;

#[ORM\Entity]
#[ORM\Table(name: 'eav_value')]
#[ORM\Index(name: 'attribute_idx', columns: ['attribute_code'])]
#[ORM\Index(name: 'family_idx', columns: ['family_code'])]
#[ORM\Index(name: 'string_search_idx', columns: ['attribute_code', 'string_value'])]
#[ORM\Index(name: 'int_search_idx', columns: ['attribute_code', 'integer_value'])]
#[ORM\Index(name: 'bool_search_idx', columns: ['attribute_code', 'bool_value'])]
#[ORM\Index(name: 'position_idx', columns: ['position'])]
class Value extends AbstractValue
{
    // Add any custom properties or methods here
}
```

> **Note**: The indexes are optional but strongly recommended for performance. Adjust them based on your query patterns.

### Step 3: Configure the Bundle

Create a configuration file for the bundle:

```yaml
# config/packages/sidus_eav_model.yaml
sidus_eav_model:
    data_class: App\Entity\Data
    value_class: App\Entity\Value
    
    # Optional: Default context values
    default_context: []
    
    # Optional: Context keys that apply to all attributes
    global_context_mask: []
```

### Step 4: Create the Database Schema

```bash
# Generate migration
php bin/console doctrine:migrations:diff

# Run migration
php bin/console doctrine:migrations:migrate

# Or directly update schema (not recommended for production)
php bin/console doctrine:schema:update --force
```

### Step 5: Clear Cache

```bash
php bin/console cache:clear
```

You're now ready to [configure your model](02-model.md)!

### Using Custom Classes (Optional)

If you need different Data classes for different families, see [Custom Classes](12-custom_classes.md).
