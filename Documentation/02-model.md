## Configuration

At this point your application should run although you won't be able to do anything without defining first your model
configuration.

### Model configuration

Please read the example in the first chapter to familiar yourself with the key features of the configuration.

If you want to test your configuration against an existing app, you can do it in the
[Clever Data Manager Starter Kit](https://github.com/cleverage/eav-manager-starter-kit/)

#### Family configuration reference

The families of your model are what would be your classes in a relational model, we call them families instead of
classes because they do not necessarily correspond to any PHP class in a strict sens. They are "data types" but such a
denomination could lead to many mistakes so we prefer to call them "families".

Each family must define at least an attribute and an attribute as label:
- The list of attribute is a simple array of attribute codes and the order in which you declare them will define the
order in which they appear in their edition form.
- The attribute as label defines which attribute should be used to display the object when calling __toString on it.

Just like a standard relational model you can define an inheritance between your families and add attributes to a child
family.

Here is a full configuration reference, the <> syntax defines a placeholder:

````yaml
sidus_eav_model:
    families:
        <familyCode>:
            # Use the translator instead of this
            label: <human-readable name of the family>
            
            # Required, except if the family is inherited
            attributeAsLabel: <attributeCode>
            
            # Can be used to set a virtual primary key on your family
            attributeAsIdentifier: <attributeCode>
            
            # Default true, can be used to define an "abstract" family
            instantiable: <boolean>
            
            # If true, the family will have only one instance accessible through DataRepository::getInstance
            singleton: <boolean>
            
            # When specified, the family will inherits its configuration
            parent: <familyCode>
            
            # Can be used with single table inheritance to declare specific business logic in a dedicated class
            data_class: <PhpClass>
            
            # Generic options that can be used by business logic/external libraries
            options: <array> 
            
            # Required
            attributes: 
                # When using a globally defined attribute
                <attributeCode>: ~
                
                # When declaring an attribute locally or overriding a globally defined one
                <attributeCode>: <AttributeConfiguration>

                # ...
````

#### Attributes configuration reference

The attributes (or properties) are defined independently from their families because they will often be reused in many
families.

An attribute will define the way a value is edited and stored in the database, each attribute has a unique code and a
type (see next chapter).

If you change the code of an attribute, all its previous values stored in the database will be discarded on next save.

Attributes can have a group, mainly to facilitate their edition but also to group them by business logic in order to
define permissions (not covered by the current bundle). You can safely change the group of an attribute without having
any effect on the way data is stored.

The full configuration reference will help you see what can be done with attributes:

````yaml
sidus_eav_model:
    attributes:
        <attributeCode>:
            # Default "string", see following chapter
            type: <attributeTypeCode>
            
            # Default null
            group: <groupCode>
            
            # Some attribute types require specific options here, example:
            options: <array>
                # Only for relations/embed: selects the allowed targets families
                allowed_families: <familyCode[]>
                
                # If true attribute will never appear in auto-generated forms
                hidden: <boolean>
                
                # Serializer options, will only work with the serializer option enabled in the global config
                serializer:
                    # Used with relations, serializer will output only the minimum fields
                    by_reference: <boolean>
                    
                    # Used with relations, serializer will output only the identifier
                    by_short_reference: <boolean>
                
                # Use with unique attributes, will check the unicity accross all families
                global_unique: <boolean>
                
                # Only for embed attributes, will remove embed data if true. Default: true
                orphan_removal: <boolean>

                # You can also use the options to pass any custom parameter to the attribute and use them in your code
                <customKey>: <customValue>

            # Standard symfony form options
            form_options: <array>
            
            # Overrides the form type of the attribute type
            form_type: <FormType>
            
            # Default value, not supported for relations for the moment
            default: <mixed>
            
            # Standard Symfony validation rules
            validation_rules: <array>
            
            # Default false, empty() PHP function is used for validation
            required: <boolean>
            
            # Default false, check if attribute is unique globally
            unique: <boolean>
            
            # Default false, see the multiple chapter
            multiple: <boolean>
            
            # Default null, see the multiple chapter
            collection: <boolean>
            
            # Default empty, see the context chapter
            context_mask: <array>
````

> **NOTE:**<br>
> Some attribute codes are reserved like:
> **id**,
> **parent**,
> **children**,
> **values**,
> **valueData**,
> **createdAt**,
> **updatedAt**,
> **currentVersion**,
> **family**
> and **currentContext**.
> If you use any of these words as attribute codes your application behavior will depends on how you  try to access the
> EAV data of the entities. **Don't do that.**

##### Attribute types

Attribute types define a common way of editing and storing data, this bundle provides the following types:
- **string**: Stored as varchar(255), edited as text input
- **text**: Stored as text, edited as textarea
- **integer**: Stored as integer, edited as text input with validation
- **decimal**: Stored as float, edited as text input with validation
- **boolean**: Stored as boolean, edited as checkbox
- **date**: Stored as date, edited as Symfony date widget
- **datetime**: Stored as datetime, edited as Symfony datetime widget
- **choice**: Stored as varchar(255), edited as choice widget (required "choices" form_options)
- **data_selector**: Stored in a real Doctrine Many-To-One relationship with a related Data object, edited as a choice
  widget. Accepts a list of allowed families in the 'allowed_families' option.
- **embed**: Stored like data but embed the edition of the foreign entity directly into the form. Requires a single family
  in the 'allowed_families' option.
- **hidden**: Stored as varchar(255), will be present in the form as a hidden input.
- **string_identifier**: Same as a string but unique and required, also automatically removes any context mask.
- **integer_identifier**: Same as an integer but unique and required, also automatically removes any context mask.

Additional attribute types can be found in the
[sidus/eav-bootstrap-bundle](https://github.com/VincentChalnot/SidusEAVBootstrapBundle):
- **html**: Stored as text, edited as TinyMCE WYSIWYG editor, featuring full control over configuration
- **switch**: Stored as boolean, edited as a nice checkbox
- **autocomplete_data_selector**: Stored like data, edited as an auto-complete input, requires the "allowed_families"
  form_option.
- **combo_data_selector**: Allow selection of the family first, then autocomplete of the data, using
  "autocomplete_data_selector".

Date and datetime are also improved with bootstrap date/time picker.

The only current limitation of the embed type is that you cannot embed a family inside the same family, this creates an
infinite loop during form building.

If you change the type of an attribute, its values will probably be discarded during the next save but it can also leads
to unexpected behaviors: don't change the type of an attribute, create a new one (and remove the previous one if need
be).
The only safe thing you can do is switch between different attributes that stores their data the same way: For example:
data, embed and autocomplete_data are safely interchangeable.

If you want more information on the multiple option, check the next chapter:

[Multiple attributes](03-multiple.md)
