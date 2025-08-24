<?php

namespace Drupal\choices\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use JsonSchema\Validator;

/**
 * Provides a form for configuring Choices.
 */
class ConfigForm extends ConfigFormBase {

  const CHOICES_INCLUDE_ADMIN = 0;
  const CHOICES_INCLUDE_NO_ADMIN = 1;
  const CHOICES_INCLUDE_EVERYWHERE = 2;

  /**
   * The JSON Schema Validator object.
   *
   * @var \JsonSchema\Validator
   */
  protected $jsonSchemaValidator;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->jsonSchemaValidator = new Validator();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['choices.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'choices_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('choices.settings');

    $form['use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use CDN'),
      '#default_value' => $config->get('use_cdn'),
      '#description' => $this->t(
        'Use the CDN instead of a locally loaded library for both the field widget AND the global choices plugin. The URLs used are: <ul><li>@link1</li><li>@link2</li></ul>',
        [
          '@link1' => 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css',
          '@link2' => 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js',
        ]
      ),
    ];

    $form['enable_globally'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Choices Globally'),
      '#default_value' => $config->get('enable_globally'),
      '#description' => $this->t('Enables choices globally on selects, specified by the choices settings. Alternatively choices can be used as a field widget to only apply on specific fields.'),
    ];

    $form['css_selector'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Apply Choices.js to the following elements'),
      '#required' => TRUE,
      '#description' => $this->t('List of CSS selectors, one per line, to apply Choices.js to, such as <code>select#edit-operation, select#edit-type</code> or <code>.choices-select</code>. Defaults to <code>select[multiple=multiple]</code> to apply Choices.js to all multiple <code>&lt;select&gt;</code> elements.'),
      '#default_value' => $config->get('css_selector'),
      '#states' => [
        'visible' => [
          ':input[name="enable_globally"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['include'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use Choices.js for admin pages and/or front end pages'),
      '#options' => [
        static::CHOICES_INCLUDE_EVERYWHERE => $this->t('Include on every page'),
        static::CHOICES_INCLUDE_ADMIN => $this->t('Include only on admin pages'),
        static::CHOICES_INCLUDE_NO_ADMIN => $this->t('Include only on front end pages'),
      ],
      '#default_value' => $config->get('include'),
      '#states' => [
        'visible' => [
          ':input[name="enable_globally"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['configuration_options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration options'),
      '#description' => $this->t('Configuration options to pass into Choices as a JSON Object. See <a href="https://github.com/Choices-js/Choices#configuration-options">https://github.com/Choices-js/Choices#configuration-options</a> for the list of available options. These configuration options will be merged with the default choices configuration options and applied on both the global and field widget choices plugins. If left empty, the default choices configuration options will be used.'),
      '#default_value' => $config->get('configuration_options'),
      '#attributes' => [
        'placeholder' => '{
    "allowHTML": true,
    "delimiter": ",",
    "searchFields": ["label", "value"]
        }',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $jsonConfigObject = $form_state->getValue('configuration_options');
    // If the input form is empty, stop further validation:
    if ($jsonConfigObject === '') {
      return;
    }
    // Setup the validation process:
    $jsonConfigsDecoded = json_decode(trim($jsonConfigObject));
    // We're only expecting an object and do NOT
    // validate anything internal, so any JSON object
    // will be allowed:
    $this->jsonSchemaValidator->validate($jsonConfigsDecoded, ['type' => 'object']);
    // Check, if the json is valid:
    if (!$this->jsonSchemaValidator->isValid()) {
      $form_state->setErrorByName('configuration_options', $this->t('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('choices.settings');
    $config
      ->set('use_cdn', $form_state->getValue('use_cdn'))
      ->set('css_selector', $form_state->getValue('css_selector'))
      ->set('include', $form_state->getValue('include'))
      ->set('configuration_options', $form_state->getValue('configuration_options'))
      ->set('enable_globally', $form_state->getValue('enable_globally'));
    $config->save();
    Cache::invalidateTags(['library_info']);
    parent::submitForm($form, $form_state);
  }

}
