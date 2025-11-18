<?php

namespace Drupal\choices\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ImmutableConfig;
use JsonSchema\Validator;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'boolean_checkbox' widget.
 *
 * @FieldWidget(
 *   id = "choices_widget",
 *   label = @Translation("Choices"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ChoicesWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'configuration_options' => '',
    ] + parent::defaultSettings();
  }

  /**
   * Returns the choices module configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The choices module configuration.
   */
  private static function getChoicesConfig(): ImmutableConfig {
    return \Drupal::config('choices.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $element, FormStateInterface $form_state) {
    $element = parent::settingsForm($element, $form_state);
    $element['configuration_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration options'),
      '#description' => $this->t('Configuration options to pass into Choices as a JSON Object. See <a href="https://github.com/Choices-js/Choices#configuration-options">https://github.com/Choices-js/Choices#configuration-options</a> for the list of available options. These configuration options will get deep merged with the choices global configuration options and overwrite values only if the deepest key value exists in both configuration options.'),
      '#default_value' => $this->getSetting('configuration_options'),
      '#element_validate' => [
        [$this, 'validateConfigOptions'],
      ],
      '#attributes' => [
        'placeholder' => '{
    "allowHTML": true,
    "delimiter": ",",
    "searchFields": ["label", "value"]
        }',
      ],
    ];
    return $element;
  }

  /**
   * Validates the settings Form.
   */
  public function validateConfigOptions(array $element, FormStateInterface $form_state) {
    $jsonConfigObject = $form_state->getValue($element['#parents']);
    // If the input form is empty, stop further validation:
    if ($jsonConfigObject === '') {
      return;
    }
    // Setup the validation process:
    $jsonConfigsDecoded = json_decode(trim($jsonConfigObject));
    // We're only expecting an object and do NOT
    // validate anything internal, so any JSON object
    // will be allowed:
    $jsonSchemaValidator = new Validator();
    $jsonSchemaValidator->validate($jsonConfigsDecoded, ['type' => 'object']);
    // Check, if the json is valid:
    if (!$jsonSchemaValidator->isValid()) {
      $form_state->setError($element, $this->t('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // The widget inherits the global settings but allows to override them:
    $widgetConfigurationOptionsString = $this->getSetting('configuration_options');
    $widgetConfigurationOptions = !empty($widgetConfigurationOptionsString) ? Json::decode($this->getSetting('configuration_options')) : [];
    $globalConfigurationOptionsString = self::getChoicesConfig()->get('configuration_options');
    $globalConfigurationOptions = !empty($globalConfigurationOptionsString) ? Json::decode($globalConfigurationOptionsString) : [];
    if (!empty($globalConfigurationOptions)) {
      // Widget configuration takes precedence over global options:
      $widgetConfigurationOptions = array_merge_recursive($globalConfigurationOptions, $widgetConfigurationOptions);
    }

    $element['#attached']['library'][] = 'choices/widget';
    // Add this fields configuration options (needs an object, not array):
    $element['#attached']['drupalSettings']['choices']['widget']['fields'][$items->getName()]['configurationOptions'] = (object) $widgetConfigurationOptions;
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
