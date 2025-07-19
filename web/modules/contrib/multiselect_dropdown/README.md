# Multiselect Dropdown

Multiselect dropdown provides a form render element which displays checkboxes in
a dropdown select-like element.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/multiselect_dropdown).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/search/multiselect_dropdown).

## Requirements

This module requires no modules outside of Drupal core.

## Recommended Modules

- [Better Exposed Filters](https://www.drupal.org/project/better_exposed_filters):
  Allows for multiselect dropdowns to be used as exposed filter widgets in 
  views.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Multiselect dropdown requires no configuration.

## Usage

To use the multiselect dropdown form element in code see the class definition 
at `\Drupal\multiselect_dropdown\Element\MultiselectDropdown` for a description
of the render array keys and a usage example.

### Form Field Widget

Entity reference and list fields can be configured to use a multiselect 
dropdown form element, provided that the field cardinality is not one. When
using the multiselect dropdown field widget, it is not possible to add buttons
to submit the form from within the multiselect dropdown.

### Views

To use the multiselect dropdown form element in views, enable the 
`multiselect_dropdown_bef` submodule and select "Allow multiple selections" in
the exposed filter settings. If the exposed filter is a taxonomy term then the
"Selection type" must also be set to "Dropdown". Once the exposed filter is
configured correctly the "Multiselect Dropdown" widget type will be available as
an "Exposed Filter Settings" option in the Better Exposed Filters form settings.

In order for focus to work correctly in views with custom templates the
`[data-multiselect-dropdown-view-results]` HTML attribute may need be added to 
the container(s) that wrap the results and/or no results message in 
`views-view.html.twig` templates. This is only necessary if the default classes
of `view-content` and `view-empty` from the starterkit theme are not present.

### Theming

Multiselect dropdowns are fully themeable. If overriding the default template 
the `data-multiselect-dropdown-*` attributes must be present for JavaScript to
work. The module provides minimal CSS to ensure the widget works well with the 
Claro admin theme and has basic functionality.

## Accessibility

Multiselect dropdowns aim to be [WCAG 2.2 AA](https://www.w3.org/TR/WCAG22/)
compliant and provides additional text for screen readers on how to interact
with the multiselect dropdown. If you find an accessibility problem create a new
issue in the 
[issue queue](https://www.drupal.org/project/issues/search/multiselect_dropdown).

## Browser Compatibility

The field widget uses the native HTML
[\<dialog\>](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dialog)
element, a newer element not widely supported until 
[2022](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dialog#browser_compatibility).
The `multiselect_dropdown_polyfill` submodule provides a polyfill through
[GoogleChrome/dialog-polyfill](https://github.com/GoogleChrome/dialog-polyfill)
which extends support through browsers released in or after 2019.

## Maintainers

Current maintainer:
- [Benjamin Baird (benabaird)](https://www.drupal.org/u/benabaird)
