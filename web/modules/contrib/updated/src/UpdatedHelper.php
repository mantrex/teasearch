<?php

namespace Drupal\updated;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides static helper methods for Last Updated module.
 */
class UpdatedHelper {

  const DISPLAY_UPDATED_PERMISSION = 'administer node last updated date';

  const PERMISSION_DENIED_MESSAGE = 'Your account does not have permission to set the updated date visibility.';

  /**
   * Returns the default value of display_updated by creating a dummy node.
   *
   * Watch issue below for getting default values without an entity.
   * https://www.drupal.org/node/2318187
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The node entity.
   *
   * @return string
   *   An string that contains the active default value.
   *
   * @see updated_form_node_type_form_alter
   */
  public static function getDefaultDisplayUpdatedValue(FormStateInterface $form_state) {
    $entityTypeManager = \Drupal::entityTypeManager();

    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $form_state->getBuildInfo()['callback_object']->getEntity();

    /** @var \Drupal\Core\Entity\EntityFormInterface $node_type_form */
    $node_type_form = $form_state->getFormObject();

    // Get a node so that we can get default values.
    $operation = $node_type_form->getOperation();
    if ($operation == 'add') {
      // Create a node with a fake bundle.
      $node = $entityTypeManager->getStorage('node')->create(['type' => $node_type->uuid()]);
    }
    else {
      // Create a node with existing bundle.
      $node = $entityTypeManager->getStorage('node')->create(['type' => $node_type->id()]);
    }

    // Since we had to create a "dummy" node, we'll get the default value from
    // the node rather than the field definition.
    return $node->display_updated->value;
  }

}
