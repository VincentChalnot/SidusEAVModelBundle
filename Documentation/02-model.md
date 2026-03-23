## Model Configuration

This chapter covers how to define your EAV model through YAML configuration.

> **Tip**: Test your configuration in a development environment first. Configuration errors will be caught at compile time.

### Understanding the Model

In the EAV model:
- **Families** define data types (like classes in OOP)
- **Attributes** define properties within families
- **Attribute Types** define how data is stored and edited

### Family Configuration

Families are the core building blocks of your model. Each family must define at least one attribute and an `attributeAsLabel`:

```yaml
sidus_eav_model:
    families:
        <FamilyCode>:
            # Human-readable name (use translations instead for i18n)
            label: <string>
            
            # Required: Which attribute to use when displaying the entity
            attributeAsLabel: <attributeCode>
            
            # Optional: Custom business identifier (must be unique, required, non-collection)
            attributeAsIdentifier: <attributeCode>
            
            # Default: true. Set false for abstract families
            instantiable: <boolean>
            
            # Default: false. Only one instance allowed
            singleton: <boolean>
            
            # Inherit configuration from another family
            parent: <FamilyCode>
            
            # Override the Data class for this family
            data_class: <FQCN>
            
            # Override the Value class for this family
            value_class: <FQCN>
            
            # Custom options for business logic
            options:
                <key>: <value>
            
            # Required: List of attributes
            attributes:
                # Reference a globally defined attribute
                <attributeCode>: ~
                
                # Define or override an attribute
                <attributeCode>:
                    <AttributeConfiguration>
```

### Attribute Configuration

Attributes define properties for your families:

```yaml
sidus_eav_model:
    # Global attributes (reusable across families)
    attributes:
        <attributeCode>:
            # Attribute type (default: string)
            type: <attributeTypeCode>
            
            # Grouping for UI organization
            group: <groupCode>
            
            # Type-specific options
            options:
                # For relations: allowed target families
                allowed_families:
                    - <FamilyCode>
                
                # Hide from auto-generated forms
                hidden: <boolean>
                
                # Check uniqueness across all families
                global_unique: <boolean>
                
                # For embeds: remove orphans (default: true)
                orphan_removal: <boolean>
                
                # Custom options
                <key>: <value>
            
            # Default value
            default: <mixed>
            
            # Symfony Validator constraints
            validation_rules:
                - NotBlank: ~
                - Length: { max: 255 }
            
            # Required field (default: false)
            required: <boolean>
            
            # Must be unique within family (default: false)
            unique: <boolean>
            
            # Allow multiple values (default: false)
            collection: <boolean>
            
            # Context keys for this attribute
            context_mask:
                - locale
                - channel
```

### Available Attribute Types

The bundle provides these built-in attribute types:

| Type | Storage | Description |
|------|---------|-------------|
| `string` | VARCHAR(255) | Short text |
| `text` | TEXT | Long text |
| `integer` | INT | Whole numbers |
| `decimal` | FLOAT | Decimal numbers |
| `boolean` | BOOLEAN | True/false |
| `date` | DATE | Date only |
| `datetime` | DATETIME | Date and time |
| `choice` | VARCHAR(255) | Selection from options |
| `hidden` | VARCHAR(255) | Hidden field |
| `data_selector` | Foreign Key | Relation to another Data entity |
| `embed` | Foreign Key | Embedded Data entity |
| `string_identifier` | VARCHAR(255) | Unique string identifier |
| `integer_identifier` | INT | Unique integer identifier |

### Reserved Attribute Codes

These codes are reserved and cannot be used:

- `id`, `identifier`
- `parent`, `children`
- `values`, `value`, `valueData`, `valuesData`
- `refererValues`, `refererDatas`
- `family`, `familyCode`
- `createdAt`, `updatedAt`
- `currentContext`
- `label`, `empty`

### Complete Example

```yaml
sidus_eav_model:
    data_class: App\Entity\Data
    value_class: App\Entity\Value
    
    # Global attributes
    attributes:
        title:
            type: string
            required: true
            validation_rules:
                - Length: { max: 255 }
        
        slug:
            type: string_identifier
        
        content:
            type: text
        
        publishedAt:
            type: datetime
        
        isActive:
            type: boolean
            default: false
    
    # Families
    families:
        # Abstract base content
        Content:
            instantiable: false
            attributeAsLabel: title
            attributes:
                title: ~
                slug: ~
                content: ~
                publishedAt: ~
                isActive: ~
        
        # Blog post extends Content
        Post:
            parent: Content
            attributeAsIdentifier: slug
            attributes:
                # Additional attributes
                author:
                    type: data_selector
                    options:
                        allowed_families:
                            - Author
                
                categories:
                    type: data_selector
                    collection: true
                    options:
                        allowed_families:
                            - Category
        
        # Author family
        Author:
            attributeAsLabel: name
            attributes:
                name:
                    type: string
                    required: true
                
                email:
                    type: string
                    unique: true
                    validation_rules:
                        - Email: ~
                
                bio:
                    type: text
        
        # Category family
        Category:
            attributeAsLabel: name
            attributes:
                name:
                    type: string
                    required: true
```

### Next Steps

- [Multiple/Collection Attributes](03-multiple.md)
- [Handling Entities](04-entities.md)
- [Validation](06-validate.md)
