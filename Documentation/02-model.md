
## Configuration
At this point your application should run although you won't be able to do anything without defining first your model
configuration.

### Model configuration
Please read the example in the first chapter to familiar yourself with the key features of the configuration.

If you want to test your configuration against an existing app, you can do it in the
[Demo Symfony Project](https://github.com/VincentChalnot/SidusEAVDemo)

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
            label: <human-readable name of the family> # Use the translator instead of this
            attributeAsLabel: <attributeCode> # Required, except if the family is inherited
            attributeAsIdentifier: <attributeCode> # Can be used to set a virtual primary key on your family
            instantiable: <boolean> # Default true, can be used to define an "abstract" family
            singleton: <boolean> # If true, the family will have only one instance accessible through DataRepository::getInstance
            parent: <familyCode> # When specified, the family will inherits its configuration
            data_class: <PhpClass> # Can be used with single table inheritance to declare specific business logic in a dedicated class
            options: <array> # generic options that can be used by business logic/external libraries
            attributes: # Required
                <attributeCode>: ~ # When using a globally defined attribute
                <attributeCode>: <AttributeConfiguration> # When declaring an attribute locally or overriding a globally defined one
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
            type: <attributeTypeCode> # Default "string", see following chapter
            group: <groupCode> # Default null
            options: <array> # Some attribute types require specific options here, example:
                allowed_families: <familyCode[]> # Only for relations/embed: selects the allowed targets families
                hidden: <boolean> # If true attribute will never appear in auto-generated forms
                serializer:
                    by_reference: <boolean> # Used with relations, serializer will output only the minimum fields
                    by_short_reference: <boolean> # Used with relations, serializer will output only the identifier
                global_unique: <boolean> # Use with unique attributes, will check the unicity accross all families
                # You can also use the options to pass any custom parameter to the attribute and use them in your code
            form_options: <array> # Standard symfony form options
            form_type: <FormType> # Overrides the form type of the attribute type
            default: <mixed> # Default value, not supported for relations for the moment
            validation_rules: <array> # Standard Symfony validation rules
            required: <boolean> # Default false, empty() PHP function is used for validation
            unique: <boolean> # Default false, check if attribute is unique globally
            multiple: <boolean> # Default false, see following chapter
            collection: <boolean> # Default null, see following chapter
            context_mask: <array> # See dedicated chapter
````

Some codes are reserved like: id, parent, children, values, valueData, createdAt, updatedAt, currentVersion, family and
currentContext. If you use any of these words as attribute codes your application behavior will depends of how you try
to access the entities' data. Don't do that.

##### Attribute types
Attribute types define a common way of editing and storing data, this bundle provides the following types:
- string: Stored as varchar(255), edited as text input
- text: Stored as text, edited as textarea
- integer: Stored as integer, edited as text input with validation
- decimal: Stored as float, edited as text input with validation
- boolean: Stored as boolean, edited as checkbox
- date: Stored as date, edited as Symfony date widget
- datetime: Stored as datetime, edited as Symfony datetime widget
- choice: Stored as varchar(255), edited as choice widget (required "choices" form_options)
- data_selector: Stored in a real Doctrine Many-To-One relationship with a related Data object, edited as a choice
widget. Accepts a list of allowed families in the 'allowed_families' option.
- embed: Stored like data but embed the edition of the foreign entity directly into the form. Requires a single family
in the 'allowed_families' option.
- hidden: Stored as varchar(255), will be present in the form as a hidden input.
- string_identifier: Same as a string but unique and required
- integer_identifier: Same as an integer but unique and required

Additional attribute types can be found in the sidus/eav-bootstrap-bundle:
- html: Stored as text, edited as TinyMCE WYSIWYG editor, featuring full control over configuration
- switch: Stored as boolean, edited as a nice checkbox
- autocomplete_data: Stored like data, edited as an auto-complete input, requires the "family" form_option.
- combo_selector: Allow selection of the family first, then autocomplete of the data, using "autocomplete_data".
Date and datetime are also improved with bootstrap date/time picker.

The only current limitation of the embed type is that you cannot embed a family inside the same family, this creates an
infinite loop during form building.

If you change the type of an attribute, its values will probably be discarded during the next save but it can also leads
to unexpected behaviors: don't change the type of an attribute, create a new one (and remove the previous one if need
be).
The only safe thing you can do is switch between different attributes that stores their data the same way: For example:
data, embed and autocomplete_data are safely interchangeable.
