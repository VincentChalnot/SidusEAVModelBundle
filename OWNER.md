# Notes for the Repository Owner

> **Note**: This file is intended for Vincent Chalnot (the repository owner) and should be deleted before the v2.0.0 release.

## Executive Summary

I've performed a major refactoring of the SidusEAVModelBundle to support PHP 8.3+, Symfony 7.0/8.0, and Doctrine ORM 3.0+. This document outlines my thoughts, decisions, and recommendations for the project going forward.

## What Was Done

### 1. Complete Namespace Reorganization

I reorganized the codebase to achieve two main goals:

1. **ORM Abstraction**: All Doctrine-specific code is now in `Bridge\Doctrine\*`. The core model classes (`Family\*`, `Attribute\*`, `Data\*`) are ORM-agnostic. This prepares the bundle for potential future ORM implementations (MongoDB, Cycle ORM, etc.) or even non-ORM persistence.

2. **Logical Grouping**: Instead of having `Model\*` and `Registry\*` as separate concepts, I grouped related classes:
   - `Family\Family`, `Family\FamilyInterface`, `Family\FamilyRegistry`
   - `Attribute\Attribute`, `Attribute\AttributeInterface`, `Attribute\AttributeRegistry`
   - `Data\DataInterface`, `Data\ValueInterface`

### 2. Modern PHP Patterns

- **Constructor Property Promotion**: Used throughout to reduce boilerplate
- **Typed Properties**: All properties have native types
- **Return Type Declarations**: All methods have return types
- **Readonly Properties**: Used where appropriate (like `$code` on Family/Attribute)
- **Named Arguments**: The API supports named arguments naturally

### 3. Doctrine ORM 3.x Compatibility

- **PHP 8 Attributes**: Replaced all Doctrine annotations with PHP 8 attributes
- **Modern Repository Pattern**: Created service repositories that can be used with Doctrine 3.x
- **Updated Event Listeners**: Compatible with Doctrine 3.x event system

### 4. Removed Dependencies

- **sidus/base-bundle**: Completely removed. The utilities were either inlined or replaced with modern PHP equivalents.

## What Wasn't Done (Intentionally)

### 1. Laravel Bridge

I did NOT create a Laravel-specific bridge. The architecture is prepared for it, but actually implementing a Laravel adapter should be a separate effort (possibly a separate package like `sidus/eav-model-laravel`).

### 2. Test Suite

I created the test directory structure but didn't write extensive tests. I recommend:
- Using Symfony's WebTestCase for integration tests
- Using PHPUnit for unit tests on the model classes
- Consider using Pest PHP for a more modern testing experience

### 3. Backward Compatibility Layer

There's no BC layer mapping old class names to new ones. A BC layer would:
- Add complexity
- Give users a false sense of compatibility
- Delay proper migration

I recommend users do a clean migration using the UPDATE.md guide.

## Recommendations

### 1. Consider Splitting the Bundle

The bundle is getting large. Consider splitting into:

- `sidus/eav-model` - Core interfaces and model classes (no Symfony dependency)
- `sidus/eav-model-bundle` - Symfony integration
- `sidus/eav-doctrine-bridge` - Doctrine ORM implementation

This would allow:
- Using the EAV model in non-Symfony contexts
- Swapping persistence layers more easily
- Cleaner dependencies

### 2. Consider Using Symfony Attributes for Configuration

Instead of YAML configuration, consider allowing PHP attributes:

```php
#[EAVFamily(code: 'Post', label: 'Blog Post')]
#[EAVAttribute('title', type: 'string', required: true)]
#[EAVAttribute('content', type: 'html')]
class Post extends AbstractData {}
```

This would be more IDE-friendly and type-safe.

### 3. Add Event System

Consider adding a proper event system for:
- Pre/post data creation
- Pre/post value change
- Attribute access hooks

This would allow plugins to modify behavior without extending classes.

### 4. Consider GraphQL Support

The EAV model maps very naturally to GraphQL. Consider creating:
- `sidus/eav-graphql-bundle` for GraphQL integration

### 5. Documentation as a Separate Repository

Consider moving documentation to a separate repository using:
- GitBook
- Docusaurus
- VuePress

This would allow versioned documentation that matches bundle versions.

## Known Issues

### 1. FamilyType Initialization

The `FamilyType` DBAL type needs the `FamilyRegistry` to be injected. Currently, this is done via a static method called from a kernel.request listener. A better solution would be to use Doctrine's TypeRegistry, but that's not straightforward in Symfony.

### 2. Value Polymorphism

The single `Value` table with multiple value columns is efficient but makes complex queries harder. Consider documenting this limitation better and providing query builder helpers.

### 3. Context System Complexity

The context system (for localization, channels, etc.) is powerful but complex. Consider:
- Better documentation with examples
- A "simple mode" without context for basic use cases
- Helper methods like `$data->inLocale('fr')->get('title')`

## Questions for You

1. **Do you want to keep supporting the old namespace with deprecation notices?** I didn't add a BC layer, but it's possible to create class aliases that trigger deprecation warnings.

2. **Should forms be a first-class feature again?** I noticed forms were removed in the v2.0-dev branch. Is this intentional? Many users probably relied on form integration.

3. **What about the ecosystem bundles?** 
   - sidus/eav-form-bundle
   - sidus/eav-bootstrap-bundle
   - sidus/filter-bundle
   
   These will need updates too. Do you want me to document what changes they'll need?

4. **Is MongoDB/non-relational support actually desired?** I prepared the architecture for it, but if it's not a real use case, we could simplify.

5. **Do you want validation to use Symfony Validator exclusively?** Currently, I integrated with Symfony Validator. For framework-agnostic validation, we'd need an abstraction layer.

## File Cleanup Checklist

Before releasing v2.0.0, remove/update:

- [ ] Delete this file (OWNER.md)
- [ ] Remove old code from root namespace (Cache/, Command/, Context/, etc.)
- [ ] Update LICENSE year range
- [ ] Update README.md badges
- [ ] Remove SensioLabsInsight badge (deprecated service)
- [ ] Consider adding PHPStan/Psalm badge
- [ ] Update couscous.yml for new documentation structure

## Personal Note

This is a great bundle with solid architecture. The EAV pattern gets a lot of criticism, but for the use cases it solves (highly dynamic data models, content management, PIMs), it's exactly right. The main opportunity I see is making it more approachable for new users while maintaining the power for advanced users.

Feel free to reach out if you have questions about any of the changes I made.

---

*This file should be deleted before the v2.0.0 release.*
