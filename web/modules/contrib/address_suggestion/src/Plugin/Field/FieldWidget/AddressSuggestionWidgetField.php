<?php

namespace Drupal\address_suggestion\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the 'address_suggestion_widget' field widget.
 *
 * @FieldWidget(
 *   id = "address_suggestion_widget",
 *   label = @Translation("Address suggestion"),
 *   field_types = {
 *     "text",
 *     "string"
 *   },
 * )
 */
final class AddressSuggestionWidgetField extends AddressSuggestionWidget implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? NULL,
    ];
    $country = $this->configFactory->get('system.date')->get('country.default');
    $fieldDefinition = $this->fieldDefinition;
    $parameters = [
      'entity_type' => $fieldDefinition->getTargetEntityTypeId(),
      'bundle' => $fieldDefinition->getTargetBundle(),
      'field_name' => $items->getName(),
    ];
    $element["value"]['#attributes']['class'][] = 'address-suggestion-widget';
    $element["value"]['#autocomplete_route_name'] = 'address_suggestion.addresses';
    $element["value"]['#autocomplete_query_parameters'] = ['country' => $country ?: FALSE];
    $element["value"]['#autocomplete_route_parameters'] = $parameters;
    $settings = $this->getSettings();
    $field_name = $this->getSetting('location_field');
    if (!empty($field_name)) {
      $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($parameters['entity_type'], $parameters['bundle']);
      $settings['type_field'] = $fieldDefinitions[$field_name]?->getType();
      if (!empty($form['#parents'])) {
        $settings['location_field'] .= ']';
      }
    }
    $form['#attached']['drupalSettings']['address_suggestion'] = $settings;
    $form["#attached"]["library"][] = 'address_suggestion/address_suggestion_widget';

    return $element;
  }

}
