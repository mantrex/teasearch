<?php

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Uri;

/**
 * The custom_field_uri data type.
 *
 * The plain value of a URI is an absolute URI represented as PHP string.
 *
 * @DataType(
 *   id = "custom_field_uri",
 *   label = @Translation("URI"),
 *   definition_class = "\Drupal\custom_field\TypedData\CustomFieldDataDefinition"
 * )
 */
class CustomFieldUri extends Uri {

}
