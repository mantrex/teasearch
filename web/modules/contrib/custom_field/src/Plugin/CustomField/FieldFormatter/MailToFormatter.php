<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;

/**
 * Plugin implementation of the 'email_mailto' formatter.
 */
#[FieldFormatter(
  id: 'email_mailto',
  label: new TranslatableMarkup('E-mail'),
  field_types: [
    'email',
  ],
)]
class MailToFormatter extends CustomFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    return [
      '#type' => 'link',
      '#title' => $value,
      '#url' => Url::fromUri('mailto:' . $value),
    ];
  }

}
