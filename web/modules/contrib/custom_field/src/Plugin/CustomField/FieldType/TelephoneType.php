<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'telephone' field type.
 */
#[CustomFieldType(
  id: 'telephone',
  label: new TranslatableMarkup('Telephone number'),
  description: new TranslatableMarkup('This field stores a telephone number in the database.'),
  category: new TranslatableMarkup('General'),
  default_widget: 'telephone',
  default_formatter: 'telephone_link',
)]
class TelephoneType extends StringType {

  /**
   * The default max length for telephone fields.
   *
   * @var int
   */
  const MAX_LENGTH = 256;

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string {
    $area_code = mt_rand(100, 999);
    $prefix = mt_rand(100, 999);
    $line_number = mt_rand(1000, 9999);

    return "$area_code-$prefix-$line_number";
  }

}
