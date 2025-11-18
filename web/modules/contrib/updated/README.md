# Last Updated

The _Last Updated_ module provides a checkbox on the node form that allows
editors the choice to display the node's "Updated" date. The placement of the
date is controlled by placing a block that is provided by this module, in the
Block Layout.

 * For a full description of the module, visit the
   [project page](https://www.drupal.org/project/updated).

 * To submit bug reports and feature suggestions, or track changes in the
   [issue queue](https://www.drupal.org/project/issues/updated).


## Requirements

This module requires no modules outside of Drupal core.


## Installation

 * Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Configure user permissions in Administration » People » Permissions (`admin/people/permissions`). The permission `Configure the display of the node last updated date` grants the ability to toggle the display of the last updated date on all node types; users with the additional `Administer content types` permission will be able to set the default display (on/off) per node type.
2. Place the "Last Updated date block" block in the active theme layout at Administration » Structure  » Block layout (`/admin/structure/block`). Typically, this would be placed in the main content region, either preceding or following the "Main page content" block.
3. Optionally set the default display for existing content types at Administration Structure Content types (`/admin/structure/types`). On the "Edit" tab for each content type, use the "Page Display Defaults" section to set whether new nodes should default to having the display of the last updated date toggled on or off (this is just the default, and can be toggled on or off on the individual node).


## Behavior & Design

Note: the permission provided by this module to display the last updated date only regulates the block provided by this module. It does not affect other methods of displaying a node's last updated date (e.g., through Layout Builder).


## Maintainers

Current maintainers:

* Jen Lampton - [jenlampton](https://www.drupal.org/u/jenlampton)
* Mark Fullmer - [mark_fullmer](https://www.drupal.org/u/mark_fullmer)

This project has been sponsored by:

* UT Austin - [The University of Texas at Austin](https://www.drupal.org/university-of-texas-at-austin)
