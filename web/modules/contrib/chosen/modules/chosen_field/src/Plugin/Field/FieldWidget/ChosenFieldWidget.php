<?php

namespace Drupal\chosen_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'chosen_select' widget.
 */
#[FieldWidget(id: 'chosen_select', label: new TranslatableMarkup('Chosen'), field_types: ['list_integer', 'list_float', 'list_string', 'entity_reference'], multiple_values: true)]
class ChosenFieldWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += [
      '#chosen' => 1,
    ];

    return $element;
  }

}
