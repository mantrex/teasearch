# Custom Field

Defines a new "Custom Field" field type that lets you create simple inline
multiple-value fields without having to use entity references.

## Features

- Multiple-value fields without entity references
- Inline field widgets (Custom Field items) using a customizable
  css-flexbox-based layout system
- Multiple field formatters: CustomField (custom theme hook), Inline, HTML List,
  Table, Custom Template (similar to views' field rewrite functionality)
- Clone existing Custom Field definitions
- Data storage and property definitions are aligned with Drupal Core field
  types.

## Why this module?

In some cases, Drupal's field api for single value fields is overkill for
storing simple field data that would be better to consolidate in a single table.
One "Custom Field" can contain many columns in a single table which can lead to
a substantial boost in performance by eliminating unnecessary joins and allowing
for simpler configuration management.

## What types of fields are not supported in a "Custom Field"?

- Entity Reference fields

## Included Custom Field widget types:

- Textfield
- Textarea - Formatted text supported
- Select
- Radios
- Checkbox
- Color
- Email
- Integer
- Decimal
- Float
- Telephone
- Uuid
- Map key/value - Serialized data of key/value pairs. Could be used as an array
  of attributes for  decoupled sites or rendered in custom theme functions.
- Url

## Disclaimer

This module is for simple, multi-fields and is not meant to be a full-on
replacement for using entity references. If you need a multi-field with nested
multi-value fields, or uses a complex field type, you're still better off using
something like Paragraphs to satisfy your needs. Paragraphs composed of
"Custom Fields" however can be a powerful combination and most likely eliminate
bloated paragraph child hierarchy.

### Note

Theres work happening in Core to improve the user experience for multi-value
fields by adding a Remove button. Until this makes it's way into core, there's
a patch available that significantly enhances the viability of multi-value
custom fields replacing the need for paragraphs and inline entity reference for
their widget functionality.
[#1038316: Allow for deletion of a single value of a multiple value field](https://www.drupal.org/project/drupal/issues/1038316).
[Patch in comment #242 generally works well in our testing:](https://www.drupal.org/project/drupal/issues/1038316#comment-14308196).

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Maintainers

- Andy Marquis - [apmsooner](https://www.drupal.org/u/apmsooner)
- Damien McKenna - [DamienMcKenna](https://www.drupal.org/u/damienmckenna)
