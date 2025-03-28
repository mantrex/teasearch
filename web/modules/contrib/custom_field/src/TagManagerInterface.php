<?php

namespace Drupal\custom_field;

/**
 * Gathers and provides the tags that can be used to wrap fields.
 */
interface TagManagerInterface {

  /**
   * The stored value representing "no markup".
   */
  const NO_MARKUP_VALUE = 'none';

  /**
   * Get the tags that can wrap fields.
   *
   * @param array $tags
   *   An optional array of tags to filter by.
   *
   * @return array
   *   An array of tags.
   */
  public function getTagOptions(array $tags): array;

}
