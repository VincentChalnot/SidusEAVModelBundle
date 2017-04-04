Sidus/EAVModelBundle Cheat-sheet
==================================

WARNING : This doc is not up-to-date

#### Family configuration reference

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
            attributes: # Required
                - <attributeCode>
                - <attributeCode>
                - <attributeCode>
````

#### Attributes configuration reference

````yaml
sidus_eav_model:
    attributes:
        <attributeCode>:
            type: <attributeType> # Default "string", see following chapter
            group: <groupCode> # Default null
            options: <object> # Some attribute types require specific options here
            form_options: <object> # Standard symfony form options
            view_options: <object> # Passed to the view (not used in this bundle)
            validation_rules: <array> # Standard Symfony validation rules
            default: <mixed> # Default value
            required: <boolean> # Default false, empty() PHP function is used for validation
            unique: <boolean> # Default false
            multiple: <boolean> # Default false, see following chapter
            context_mask: <array> # See dedicated chapter
````

**Reserved words for attribute codes:** id, parent, children, values, valueData, createdAt, updatedAt, currentVersion, family and currentContext.

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
- data: Stored in a real Doctrine Many-To-One relationship with a related Data object, edited as a choice widget, requires the "family" form_option.
- embed: Stored like data but embed the edition of the foreign entity directly into the form, requires the "family" form_option.
- hidden: Stored as varchar(255), will be present in the form as a hidden input.
- string_identifier: Same as a string but unique and required
- integer_identifier: Same as an integer but unique and required

Additional attribute types can be found in the sidus/eav-bootstrap-bundle:
- html: Stored as text, edited as TinyMCE WYSIWYG editor, featuring full control over configuration
- switch: Stored as boolean, edited as a nice checkbox
- autocomplete_data: Stored like data, edited as an auto-complete input, requires the "family" form_option.
- combo_selector: Allow selection of the family first, then autocomplete of the data, using "autocomplete_data".
Date and datetime are also improved with bootstrap date/time picker.

#### Adding new attribute types using existing storage properties

````yaml
services:
    my_namespace.attribute_type.my_attribute:
        class: '%sidus_eav_model.attribute_type.default.class%'
        arguments: [<my_attribute_code>, <storage_property>, <my_namespace_form_type>]
        tags:
            - { name: sidus.attribute_type }
````
