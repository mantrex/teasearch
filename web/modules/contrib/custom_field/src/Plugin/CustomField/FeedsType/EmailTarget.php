<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'string' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   mark_unique = TRUE,
 * )
 */
class EmailTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $name = $this->configuration['name'];
    $value = is_string($value) ? trim($value) : $value;
    if (empty($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
      $value = NULL;
    }
    if (!empty($value) && $configuration[$name]['defuse']) {
      $value .= '_test';
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'defuse' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(int $delta, array $configuration) {
    $form['defuse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defuse e-mail addresses'),
      '#default_value' => $configuration['defuse'],
      '#description' => $this->t('This appends _test to all imported e-mail addresses to ensure they cannot be used as recipients.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $configuration): array {
    $summary[] = $configuration['defuse'] ?
      $this->t('Addresses <strong>will</strong> be defused.') :
      $this->t('Addresses will not be defused.');

    return $summary;
  }

}
