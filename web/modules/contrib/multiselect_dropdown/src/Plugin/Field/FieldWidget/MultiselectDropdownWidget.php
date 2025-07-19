<?php

declare(strict_types=1);

namespace Drupal\multiselect_dropdown\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\multiselect_dropdown\ModalType;

/**
 * Multiselect dropdown field widget.
 */
#[FieldWidget(
  id: 'multiselect_dropdown',
  label: new TranslatableMarkup('Multiselect Dropdown'),
  field_types: [
    'entity_reference',
    'list_integer',
    'list_float',
    'list_string',
  ],
  multiple_values: TRUE,
)]
final class MultiselectDropdownWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getFieldStorageDefinition()->getCardinality() !== 1;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function defaultSettings(): array {
    return [
      'label_aria' => t('Toggle the list of items'),
      'label_none' => t('No Items Selected'),
      'label_all' => t('All Items Selected'),
      'label_single' => t('%d Item Selected'),
      'label_plural' => t('%d Items Selected'),
      'label_select_all' => '',
      'label_select_none' => '',
      'search_title' => '',
      'search_title_display' => '',
      'search_placeholder' => '',
      'search_character_threshold' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsForm(
    array $form,
    FormStateInterface $form_state,
  ): array {
    $form = [];

    $form['label_aria'] = MultiselectDropdownWidget::formLabelAria() + [
      '#default_value' => $this->getSetting('label_aria'),
    ];

    $form['label_none'] = MultiselectDropdownWidget::formLabelNone() + [
      '#default_value' => $this->getSetting('label_none'),
    ];

    $form['label_all'] = MultiselectDropdownWidget::formLabelAll() + [
      '#default_value' => $this->getSetting('label_all'),
    ];

    $form['label_single'] = MultiselectDropdownWidget::formLabelSingle() + [
      '#default_value' => $this->getSetting('label_single'),
    ];

    $form['label_plural'] = MultiselectDropdownWidget::formLabelPlural() + [
      '#default_value' => $this->getSetting('label_plural'),
    ];

    $form['label_select_all'] = MultiselectDropdownWidget::formLabelSelectAll() + [
      '#default_value' => $this->getSetting('label_select_all'),
    ];

    $form['label_select_none'] = MultiselectDropdownWidget::formLabelSelectNone() + [
      '#default_value' => $this->getSetting('label_select_none'),
    ];

    $form['search_title'] = MultiselectDropdownWidget::formSearchTitle() + [
      '#default_value' => $this->getSetting('search_title'),
    ];

    $form['search_title_display'] = MultiselectDropdownWidget::formSearchTitleDisplay('[name$="[search_title]"]') + [
      '#default_value' => $this->getSetting('search_title_display'),
    ];

    $form['search_placeholder'] = MultiselectDropdownWidget::formSearchPlaceholder('[name$="[search_title]"]') + [
      '#default_value' => $this->getSetting('search_placeholder'),
    ];

    $form['search_character_threshold'] = MultiselectDropdownWidget::formSearchCharacterThreshold('[name$="[search_title]"]') + [
      '#default_value' => $this->getSetting('search_character_threshold'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsSummary(): array {
    $summary = [];

    $summary['labels'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Labels: @labels', [
        '@labels' => implode(', ', [
          $this->getSetting('label_aria'),
          $this->getSetting('label_none'),
          $this->getSetting('label_all'),
          $this->getSetting('label_single'),
          $this->getSetting('label_plural'),
        ]),
      ]),
    ];

    $summary['label_select_all'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Select all button: @value', [
        '@value' => $this->getSetting('label_select_all')
          ? $this->t('Yes')
          : $this->t('No'),
      ]),
    ];

    $summary['label_select_none'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Select none button: @value', [
        '@value' => $this->getSetting('label_select_none')
          ? $this->t('Yes')
          : $this->t('No'),
      ]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state,
  ): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state) + [
      '#type' => 'multiselect_dropdown',
      '#default_value' => $this->getSelectedOptions($items),
      '#options' => $this->getOptions($items->getEntity()),
      '#label_aria' => $this->getSetting('label_aria'),
      '#label_none' => $this->getSetting('label_none'),
      '#label_all' => $this->getSetting('label_all'),
      '#label_single' => $this->getSetting('label_single'),
      '#label_plural' => $this->getSetting('label_plural'),
      '#search_title' => $this->getSetting('search_title'),
      '#search_title_display' => $this->getSetting('search_title_display'),
      '#search_placeholder' => $this->getSetting('search_placeholder'),
      '#search_character_threshold' => $this->getSetting('search_character_threshold'),
      '#modal_breakpoint' => ModalType::Dialog->value,
    ];
    $element['#attached']['library'][] = 'multiselect_dropdown/field_widget';

    if ($select_all = $this->getSetting('label_select_all')) {
      $element['#label_select_all'] = $select_all;
    }

    if ($select_none = $this->getSetting('label_select_none')) {
      $element['#label_select_none'] = $select_none;
    }

    return $element;
  }

  /**
   * Aria-label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelAria(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Accessible Label'),
      '#description' => t('An <code>aria-label</code> added to the toggle button.'),
      '#required' => TRUE,
    ];
  }

  /**
   * No items selected label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelNone(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('None Label'),
      '#description' => t('The toggle button label displayed when no items are selected.<br>The <code>%d</code> placeholder is replaced by the number of selected items.'),
      '#required' => TRUE,
    ];
  }

  /**
   * All items selected label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelAll(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('All Label'),
      '#description' => t('The toggle button label displayed when all items are selected.<br>The <code>%d</code> placeholder is replaced by the number of selected items.'),
      '#required' => TRUE,
    ];
  }

  /**
   * Single item selected label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelSingle(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Single Label'),
      '#description' => t('The toggle button label displayed when one item is selected.<br>The <code>%d</code> placeholder is replaced by the number of selected items.'),
      '#required' => TRUE,
    ];
  }

  /**
   * Multiple items selected label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelPlural(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Plural Label'),
      '#description' => t('The toggle button label displayed when more than one item is selected.<br>The <code>%d</code> placeholder is replaced by the number of selected items.'),
      '#required' => TRUE,
    ];
  }

  /**
   * All items selected label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelSelectAll(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Select All Button'),
      '#description' => t('The label of the select all button. Leave blank to omit.'),
      '#required' => FALSE,
    ];
  }

  /**
   * No items selected label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formLabelSelectNone(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Deselect All Button'),
      '#description' => t('The label of the deselect all button. Leave blank to omit.'),
      '#required' => FALSE,
    ];
  }

  /**
   * Search input label configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool>
   */
  public static function formSearchTitle(): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Search Input Label'),
      '#description' => t('The label of the search input. Leave blank to omit.'),
      '#required' => FALSE,
    ];
  }

  /**
   * Search input label display configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool|mixed[]>
   */
  public static function formSearchTitleDisplay(string $title_selector): array {
    return [
      '#type' => 'select',
      '#title' => t('Search Input Label Display'),
      '#description' => t('The label display of the search input.'),
      '#options' => [
        '' => t('- Select -'),
        'before' => t('Before'),
        'after' => t('After'),
        'invisible' => t('Invisible'),
        'attribute' => t('Attribute'),
      ],
      '#states' => [
        'visible' => [
          $title_selector => [
            'filled' => TRUE,
          ],
        ],
        'required' => [
          $title_selector => [
            'filled' => TRUE,
          ],
        ],
      ],
    ];
  }

  /**
   * Search input placeholder text configuration form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string|bool|mixed[]>
   */
  public static function formSearchPlaceholder(string $title_selector): array {
    return [
      '#type' => 'textfield',
      '#title' => t('Search Input Placeholder'),
      '#description' => t('The placeholder text for the search input. Leave blank to omit.'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          $title_selector => [
            'filled' => TRUE,
          ],
        ],
      ],
    ];
  }

  /**
   * Search input character threshold configuration form input.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|int|string|bool|mixed[]>
   */
  public static function formSearchCharacterThreshold(string $title_selector): array {
    return [
      '#type' => 'number',
      '#title' => t('Search Character Threshold'),
      '#description' => t('The number of required characters to be entered before filtering the list.'),
      '#min' => 0,
      '#states' => [
        'visible' => [
          $title_selector => [
            'filled' => TRUE,
          ],
        ],
        'required' => [
          $title_selector => [
            'filled' => TRUE,
          ],
        ],
      ],
    ];
  }

}
