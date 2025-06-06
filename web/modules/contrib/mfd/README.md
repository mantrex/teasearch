# Multilanguage Form Display (mfd)

Multilanguage Form Display exists to make your translated content editing
a little easier.  It provides a unified translation editing experience on a single
form. 
 
### Documentation

You can find detailed step-by-step installation and usage instructions here:
https://www.drupal.org/docs/contributed-modules/multilanguage-form-display

### Known Issues

Due to a bug in the WidgetBase class, a core patch is required to use this module.


Otherwise, you will get the following error:

```Warning: Illegal string offset '_original_delta' in /app/web/core/lib/Drupal/Core/Field/WidgetBase.php on line 372```

Please refer to the relevant issue page for details:
 - https://www.drupal.org/project/drupal/issues/2991986.

The patch is located here: 
 -  https://www.drupal.org/files/issues/2019-06-18/2991986-6.patch
    
Please follow the How-To install a patch instructions:
  - https://duvien.com/blog/how-apply-patch-file-composer-based-drupal-89

### Note
This module works really nicely with a multi-column layout. You can active these by using the field_layout module and picking a layout for the form. Then, by placing the default fields in one column and the <strong>multilingual form display</strong> in a second column, you get a nice side-by-side editing experience.

### Drupal.org Project page

  - https://www.drupal.org/docs/contributed-modules/multilanguage-form-display