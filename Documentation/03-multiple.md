## Multiple & collection option

The "collection" option allows you to add multiple values for the same attribute and because of the way the EAV model
works, all attribute types are compatible with this option.

The "collection" option defines the way the model works, not the form! If you want to automatically generate a Symfony
collection of form widgets to edit this attribute you need to use the "multiple" option.

Basically, the multiple option automatically enables the "collection" option but provides in the same time a way to
automatically generate compatible form types to edit your attribute.

This option will probably not behave well in forms without the bootstrap-collection extension of the
sidus/eav-bootstrap-bundle:

[https://github.com/VincentChalnot/SidusEAVBootstrapBundle](https://github.com/VincentChalnot/SidusEAVBootstrapBundle)

You can safely switch a single-value attribute to multiple, the current values will be kept as the first value of the
collection. If you switch from an multiple attribute back to a single-valued one, only the first value of the collection
will be kept during the next save of the entity.

When using the "required" option, the collection will pass validation if containing at least one element (event if the
element itself is empty).

When using only the collection option (with multiple: false by default), your form type has to provide a way to edit an
array of values. For example the following configuration is perfectly correct:

````yaml
sidus_eav_model:
    attributes:
        checkboxesExample:
            # Store data as a string
            type: choice
            
            # This means the model is waiting for an array of values, so an array of strings
            collection: true
            
            form_options:
                # This trigger the ability of the ChoiceType to provide an array of values
                multiple: true
                
                # Just to have checkboxes instead of an ugly multiselect in the rendering
                expanded: true

                choices:
                    bar: foo
                    42: life
````

If you were to use the ````multiple```` option in the following example the form would not render as expected and saving
data would result in an exception.

An attribute configured to be multiple but not a collection doesn't make any sense and will trigger an exception during
the compilation of the model.

### WARNING
When using the multiple option, the form will automatically generate a collection form type. In order to allow to switch
the attribute from multiple to not multiple, the standard options for the collection have been exchanged with the
'entry_options' option, meaning that if you want to pass options to the collection you will have to use the
'collection_options' option. The DataType will automatically provide the proper options for the form type and remove the
'collection_options' if the attribute is not multiple.
