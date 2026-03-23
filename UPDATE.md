# Upgrading to Sidus EAV Model Bundle v2.0

This document outlines the changes required to upgrade from v1.3 to v2.0 of the Sidus EAV Model Bundle.

> **⚠️ WARNING**: Version 2.0 contains significant breaking changes. This is a major rewrite of the bundle designed to work with modern PHP and Symfony versions. Plan your migration carefully.

## Requirements

| Component | v1.3 | v2.0 |
|-----------|------|------|
| PHP | ≥5.6 | ≥8.3 |
| Symfony | 2.8/3.0/4.0 | 7.0/8.0 |
| Doctrine ORM | 2.5+ | 3.0+ |
| Doctrine DBAL | 2.x | 4.0+ |

## Major Changes Overview

### 1. Namespace Reorganization

The bundle has been completely reorganized to separate concerns and prepare for future ORM-agnostic support.

**Old structure:**
```
Sidus\EAVModelBundle\
├── Cache\
├── Command\
├── Context\
├── DependencyInjection\
├── Doctrine\
├── Entity\
├── Event\
├── Exception\
├── Manager\
├── Model\
├── Registry\
├── Validator\
```

**New structure:**
```
Sidus\EAVModelBundle\
├── Attribute\          # AttributeType, Attribute, AttributeRegistry
├── Bridge\
│   └── Doctrine\       # All Doctrine-specific code
│       ├── Entity\
│       ├── EventListener\
│       ├── Repository\
│       └── Type\
├── Context\
├── Data\               # DataInterface, ValueInterface
├── DependencyInjection\
├── Exception\
├── Family\             # Family, FamilyRegistry
└── Validator\
```

### 2. Class Renames

| Old Class | New Class |
|-----------|-----------|
| `Model\Family` | `Family\Family` |
| `Model\FamilyInterface` | `Family\FamilyInterface` |
| `Model\Attribute` | `Attribute\Attribute` |
| `Model\AttributeInterface` | `Attribute\AttributeInterface` |
| `Model\AttributeType` | `Attribute\AttributeType` |
| `Model\AttributeTypeInterface` | `Attribute\AttributeTypeInterface` |
| `Registry\FamilyRegistry` | `Family\FamilyRegistry` |
| `Registry\AttributeRegistry` | `Attribute\AttributeRegistry` |
| `Registry\AttributeTypeRegistry` | `Attribute\AttributeTypeRegistry` |
| `Entity\DataInterface` | `Data\DataInterface` |
| `Entity\ValueInterface` | `Data\ValueInterface` |
| `Entity\ContextualDataInterface` | `Data\ContextualDataInterface` |
| `Entity\ContextualValueInterface` | `Data\ContextualValueInterface` |
| `Entity\AbstractData` | `Bridge\Doctrine\Entity\AbstractData` |
| `Entity\AbstractValue` | `Bridge\Doctrine\Entity\AbstractValue` |
| `Entity\DataRepository` | `Bridge\Doctrine\Repository\DataRepository` |
| `Entity\ValueRepository` | `Bridge\Doctrine\Repository\ValueRepository` |
| `Doctrine\Types\FamilyType` | `Bridge\Doctrine\Type\FamilyType` |
| `Event\DoctrineMetadataListener` | `Bridge\Doctrine\EventListener\DoctrineMetadataListener` |
| `Event\OrphanEmbedRemovalListener` | `Bridge\Doctrine\EventListener\OrphanEmbedRemovalListener` |

### 3. Doctrine ORM Mapping Changes

#### From Annotations to PHP 8 Attributes

**v1.3 (Annotations):**
```php
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="my_data")
 */
class MyData extends AbstractData
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $customField;
}
```

**v2.0 (PHP 8 Attributes):**
```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'my_data')]
class MyData extends AbstractData
{
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $customField = null;
}
```

### 4. Service Configuration Changes

#### YAML to PHP Configuration

The bundle now uses PHP-based service configuration internally. Your own configuration in YAML still works, but the format has been updated:

**v1.3:**
```yaml
sidus_eav_model:
    data_class: App\Entity\Data
    value_class: App\Entity\Value
    families:
        Post:
            attributeAsLabel: title
            attributes:
                title:
                    required: true
```

**v2.0:** (Same YAML format, but note some internal changes)
```yaml
sidus_eav_model:
    data_class: App\Entity\Data
    value_class: App\Entity\Value
    default_context: []
    global_context_mask: []
    families:
        Post:
            attributeAsLabel: title
            attributes:
                title:
                    required: true
```

### 5. Dependency Removal

The `sidus/base-bundle` dependency has been removed. If you were using utilities from that bundle, you'll need to replace them:

| Old | Replacement |
|-----|-------------|
| `TranslatableTrait` | Inline translation logic or custom trait |
| `DateTimeUtility::parse()` | Native `DateTime` constructor or `DateTimeImmutable::createFromFormat()` |
| `DebugInfoUtility` | PHP 8's `__debugInfo()` magic method |
| `GenericCompilerPass` | Custom compiler passes |

### 6. Type Safety Improvements

All classes now use strict typing:

```php
// v1.3
public function getAttribute($code)

// v2.0
public function getAttribute(string $code): AttributeInterface
```

Update your code to handle the new type hints and return types.

### 7. Property Visibility and Typing

All properties now have native PHP types:

```php
// v1.3
/** @var int */
protected $id;

// v2.0
protected ?int $id = null;
```

### 8. Context Management

The context management system has been simplified:

```php
// v1.3
$family->getContext($contextManager);

// v2.0
$family->getContext(); // Uses injected ContextManagerInterface
```

## Migration Steps

### Step 1: Update Requirements

Update your `composer.json`:

```json
{
    "require": {
        "php": ">=8.3",
        "sidus/eav-model-bundle": "^2.0",
        "symfony/framework-bundle": "^7.0|^8.0",
        "doctrine/orm": "^3.0",
        "doctrine/doctrine-bundle": "^2.12"
    }
}
```

### Step 2: Update Entity Classes

1. Change the parent class namespace:
   ```php
   // Old
   use Sidus\EAVModelBundle\Entity\AbstractData;
   
   // New
   use Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractData;
   ```

2. Convert Doctrine annotations to PHP 8 attributes:
   ```php
   // Old
   /**
    * @ORM\Entity
    * @ORM\Table(name="my_data")
    */
   class MyData extends AbstractData
   
   // New
   #[ORM\Entity]
   #[ORM\Table(name: 'my_data')]
   class MyData extends AbstractData
   ```

3. Add property types:
   ```php
   // Old
   protected $customProperty;
   
   // New
   protected ?string $customProperty = null;
   ```

### Step 3: Update Imports

Search and replace the following in your codebase:

```
Sidus\EAVModelBundle\Model\Family → Sidus\EAVModelBundle\Family\Family
Sidus\EAVModelBundle\Model\FamilyInterface → Sidus\EAVModelBundle\Family\FamilyInterface
Sidus\EAVModelBundle\Model\Attribute → Sidus\EAVModelBundle\Attribute\Attribute
Sidus\EAVModelBundle\Model\AttributeInterface → Sidus\EAVModelBundle\Attribute\AttributeInterface
Sidus\EAVModelBundle\Entity\DataInterface → Sidus\EAVModelBundle\Data\DataInterface
Sidus\EAVModelBundle\Entity\ValueInterface → Sidus\EAVModelBundle\Data\ValueInterface
Sidus\EAVModelBundle\Entity\AbstractData → Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractData
Sidus\EAVModelBundle\Entity\AbstractValue → Sidus\EAVModelBundle\Bridge\Doctrine\Entity\AbstractValue
Sidus\EAVModelBundle\Registry\FamilyRegistry → Sidus\EAVModelBundle\Family\FamilyRegistry
Sidus\EAVModelBundle\Registry\AttributeRegistry → Sidus\EAVModelBundle\Attribute\AttributeRegistry
```

### Step 4: Update Service References

If you're injecting services directly, update the class names:

```yaml
# Old
App\Service\MyService:
    arguments:
        - '@Sidus\EAVModelBundle\Registry\FamilyRegistry'

# New  
App\Service\MyService:
    arguments:
        - '@Sidus\EAVModelBundle\Family\FamilyRegistry'
```

### Step 5: Update Custom Attribute Types

If you have custom attribute types:

```php
// v1.3
use Sidus\EAVModelBundle\Model\AttributeType;

class MyCustomType extends AttributeType
{
    public function __construct()
    {
        parent::__construct('my_custom', 'stringValue');
    }
}

// v2.0
use Sidus\EAVModelBundle\Attribute\AttributeType;

class MyCustomType extends AttributeType
{
    public function __construct()
    {
        parent::__construct('my_custom', 'stringValue');
    }
}
```

### Step 6: Update Commands

If you were using the `PurgeOrphanDataCommand` or `FixDataDiscriminatorsCommand`, these are no longer included by default. You can recreate them using the new Doctrine repository classes if needed.

### Step 7: Run Doctrine Migrations

After updating your entities, generate and run Doctrine migrations:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Step 8: Clear Cache

```bash
php bin/console cache:clear
```

## Deprecated Features

The following features have been removed and have no direct replacement:

1. **Forms**: Form handling has been moved to the separate `sidus/eav-form-bundle` package (v2.0+)
2. **Serialization**: Removed from core, implement your own or use `symfony/serializer`
3. **ParamConverters**: Removed, use modern Symfony request handling
4. **IDE Autocomplete Cache Warmer**: Removed, modern IDEs handle this better

## New Features

### 1. Laravel Compatibility Preparation

The new architecture separates Doctrine-specific code into a bridge, making it easier to create a Laravel bridge in the future.

### 2. Better Type Safety

Full PHP 8.3+ type safety throughout the codebase.

### 3. Simpler Service Configuration

PHP-based service configuration that's easier to understand and debug.

### 4. Modern PHP Patterns

- Constructor property promotion
- Named arguments support
- Match expressions
- Enum support (where applicable)

## Getting Help

If you encounter issues during migration:

1. Check the [GitHub Issues](https://github.com/VincentChalnot/SidusEAVModelBundle/issues)
2. Create a new issue with details about your specific migration problem
3. Check the updated documentation in the `/Documentation` folder

## Version Compatibility Matrix

| EAVModelBundle | PHP | Symfony | Doctrine ORM | Doctrine DBAL |
|----------------|-----|---------|--------------|---------------|
| 1.3.x | 5.6 - 7.4 | 2.8 - 4.4 | 2.5 - 2.x | 2.x |
| 2.0.x | 8.3+ | 7.0 - 8.0 | 3.0+ | 4.0+ |
