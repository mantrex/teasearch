<?php

namespace Drupal\address_suggestion\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\CountryDefaultWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'continent_country' widget.
 *
 * @FieldWidget(
 *   id = "country_continent",
 *   label = @Translation("Continent filter country"),
 *   field_types = {
 *     "address_country"
 *   },
 * )
 */
final class CountryContinentWidget extends CountryDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'continent' => FALSE,
      'multi' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['multi'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multi options'),
      '#description' => $this->t('List checkboxes or select.'),
      '#default_value' => $this->getSetting('multi'),
    ];
    $element['continent'] = [
      '#type' => $this->getSetting('continent') ? 'checkboxes' : 'select',
      '#title' => $this->t('Activate Continent'),
      '#options' => $this->continent(),
      '#description' => $this->t('If no continents are selected, this filter will not be used.'),
      '#default_value' => $this->getSetting('continent'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if (!empty($this->getSetting('continent'))) {
      $summary[] = implode(' ', [
        $this->t('Activate Continent:'),
        ...$this->continent($this->getSetting('continent')),
      ]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function continent($selected = []) {
    $continent = [
      'af' => $this->t('Africa'),
      'as' => $this->t('Asia'),
      'eu' => $this->t('Europe'),
      'na' => $this->t('North America'),
      'sa' => $this->t('South America'),
      'oc' => $this->t('Oceania'),
      'an' => $this->t('Antarctica'),
    ];
    if (!empty($selected)) {
      return array_intersect_key($continent, array_flip($selected));
    }
    return $continent;
  }

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $continents = $this->continent();
    $continentSettings = array_filter($this->getSetting('continent'));
    if (!empty($continentSettings)) {
      $type = $this->getSetting('multi') ? 'checkboxes' : 'select';
      $element['continent'] = [
        '#type' => $type,
        '#options' => $continents,
        '#empty_value' => '',
        '#default_value' => $continentSettings,
        '#title' => $this->t('Continent'),
        '#description' => $this->t('Select a continent'),
        '#validated' => TRUE,
        '#attributes' => [
          'class' => ['continent-suggestion'],
        ],
        '#weight' => 0,
      ];
      $element["value"]["#weight"] = 1;
    }
    $form['#attached']['library'][] = 'address_suggestion/continent';

    return $element;
  }

}
