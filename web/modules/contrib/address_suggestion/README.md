# Address Suggestion

The Address Suggestion module is a powerful tool for automatically
suggesting addresses during form input. It is an alternative to the
deprecated Address Autocomplete module, offering more robust features
and better integration.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/address_suggestion).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/address_suggestion).


## Features

- Automatic address suggestions with country-specific precision.
- Configurable per field widget.
- Supports both plain text fields and address fields.
- Supports CKEditor5 for address fields.
- House number auto-addition.
- Integration with Geo fields and Geolocation fields.

## Installation

Install as you would normally install a contributed Drupal module.
For further information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Add a Geo field, You can map it in your address field widget settings.
You can use OpenStreetMap in the field formatter.
Display Google Maps in ckeditor 5.

## For developer

You can create your custom address provider in your custom module.
my_custom_module/src/Plugin/AddressProvider/CustomMaps.php

## Maintainers

- NGUYEN Bao - [lazzyvn](https://www.drupal.org/u/lazzyvn)
