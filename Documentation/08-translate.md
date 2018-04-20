## How to translate the model

In order to translate the labels of the families and attributes, as well as some few other things, you only need to
create a standard Symfony translation file with the proper syntax.

| WARNING |
| ------- |
| Data translation is done using [*EAV* context](09-context.md) |

### Translating Families

The label of a family can be directly provided in the configuration of the family by setting the "label" configuration
key. This is not recommended as it will not allow you to translate the attribute in multiple languages.

The translation system will look for this keys in order to translate a family:

````yml
eav:
    family:
        <family_code>:
            label: <label>
````

### Translating Attributes

The label of an attribute can be directly provided in the configuration of the attribute by setting the "label"
configuration key. This is not recommended as it will not allow you to translate the attribute in multiple languages.

The translation system will first look for this keys in order to translate an attribute:

````yml
eav:
    family:
        <family_code>:
            attribute:
                <attribute_code>:
                    label: <label>
````

However, if your attribute exists in multiple families and has the same label, you can use this syntax instead:

````yml
eav:
    attribute:
        <attribute_code>:
            label: <label>
````

### Translating error messages

The following keys will be tested:

````yml
eav:
    family:
        <family_code>:
            attribute:
                <attribute_code>:
                    validation:
                        <type>: <error_msg>
eav:
    attribute:
        <attribute_code>:
            validation:
                <type>: <error_msg>
eav:
    validation:
        <type>: <error_msg>
````

The following translations parameters will be available:
- ````%attribute%````: The translated label of the attribute.
- ````%family%````: The translated label of the family.
