<?php

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The custom_field_uri data type.
 */
#[DataType(
  id: 'custom_field_uri',
  label: new TranslatableMarkup('URI'),
  description: new TranslatableMarkup('The plain value of a URI is an absolute URI represented as PHP string.'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldUri extends Uri {

}
