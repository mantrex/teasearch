
# Choices
- [Choices](#choices)
  - [Introduction](#introduction)
  - [Requirements](#requirements)
  - [Limitations](#limitations)
  - [Installation](#installation)
    - [With composer](#with-composer)
    - [Without composer](#without-composer)
    - [Using the Content Delivery Network](#using-the-content-delivery-network)
  - [Features and Configuration](#features-and-configuration)
    - [Choices Global](#choices-global)
    - [Choices Field Widget](#choices-field-widget)

## Introduction
Drupal implementation of the [Choices.js](https://github.com/Choices-js/Choices)
plugin.

Choices is a vanilla, lightweight, configurable `<select>` input plugin, which
renders "selects" as boxes or lists, without utilizing jQuery.

A Demo of the Plugin can be seen [here](https://choices-js.github.io/Choices/).

## Requirements
This module requires the following libraries:

  - [Choices.js](https://choices-js.github.io/Choices/)

## Limitations
This module currently doesn't support `<select>` elements inside WYSIWYG Fields.

## Installation

### With composer

 - Ensure asset packagist has been set up for your project.
   - Visit
     https://www.drupal.org/docs/develop/using-composer/manage-dependencies#third-party-libraries
     for further information.
 - Run `composer require bower-asset/choices.js drupal/choices`
 - Install the module as you would normally install a contributed Drupal module.

### Without composer
 - Download the [Choices.js](https://github.com/jshjohnson/Choices) library to
   your project's `/libraries` directory.
 - Make sure the path to the relevant JS files is as followed:
   - `/libraries/choices.js/public/assets/scripts/choices.min.js`
   - `/libraries/choices.js/public/assets/styles/choices.min.css`
 - Install the module as you would normally install a contributed Drupal module.
 - Visit the [Installing Modules Help Page](https://www.drupal.org/node/1897420)
   if you have trouble installing the module.

### Using the Content Delivery Network
- Install the module as you would normally install a contributed Drupal module.
- Go to Home -> Administration -> Configuration -> User interface -> Choices
("/admin/config/user-interface/choices").
- Check the "Use CDN" checkbox, save and flush all caches.
- Done.

## Features and Configuration
This module provides two ways of integrating the choices plugin into your site:
1. A global setting to enable choices on your whole site.
2. A field widget, to apply choices on a single field.

### Choices Global
To enable choices globally, go to Home -> Administration -> Configuration ->
User interface -> Choices ("/admin/config/user-interface/choices") and check the
"Enable Choices Globally" checkbox. After that, you have the options to apply
choices globally on specific css selectors and only on admin and / or front-end
pages. Furthermore, you can add
[Choices configuration options](https://github.com/Choices-js/Choices#configuration-options)
which will merge with the default choices settings (This will always consider
the deepest json key set).

### Choices Field Widget
To use choices as a field widget, go to the content type field you want to apply
the choices field widget to
("/admin/structure/types/manage/my-content-type/form-display"). And set the
"Choices" widget. Note that the "Choices" widget, only applies on Entity
Reference, List (integer), List (float) and List (text) fields!
Similar to "Choices Global" you can also set
[Choices configuration options](https://github.com/Choices-js/Choices#configuration-options)
per field widget in the widget configuration. These options will merge with the
Global choices configuration options set on
"/admin/config/user-interface/choices", prioritizing the widget options, if
there is a duplicate key (This will always consider the deepest json key set).
So the options hierarchy will be as follows:

Widget configuration options > Global configuration Options > Default
configuration options.
