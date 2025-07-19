<?php

declare(strict_types=1);

namespace Drupal\multiselect_dropdown_bef\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\multiselect_dropdown\ModalType;
use Drupal\multiselect_dropdown\Plugin\Field\FieldWidget\MultiselectDropdownWidget;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;

/**
 * Multiselect dropdown widget for Better Exposed Filters.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "multiselect_dropdown",
 *   label = @Translation("Multiselect Dropdown"),
 * )
 */
final class MultiselectDropdownFilterWidget extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function isApplicable(
    $filter = NULL,
    array $filter_options = [],
  ): bool {
    $base_applicable = parent::isApplicable($filter, $filter_options)
      && $filter_options['expose']['multiple'];
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    return match (\get_class($filter)) {
      TaxonomyIndexTid::class => $base_applicable && $filter_options['type'] === 'select',
      default => $base_applicable,
    };
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + MultiselectDropdownWidget::defaultSettings() + [
      // In the context of views, no selections is usually synonymous with all.
      'label_none' => MultiselectDropdownWidget::defaultSettings()['label_all'],
      'label_close' => '',
      'label_submit' => '',
      'label_clear' => '',
      'modal_type' => ModalType::Breakpoint->value,
      'modal_breakpoint' => 768,
      'default_open' => FALSE,
      'persist_open' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state,
  ): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['label_aria'] = MultiselectDropdownWidget::formLabelAria() + [
      '#default_value' => $this->configuration['label_aria'],
    ];

    $form['label_none'] = MultiselectDropdownWidget::formLabelNone() + [
      '#default_value' => $this->configuration['label_none'],
    ];

    $form['label_all'] = MultiselectDropdownWidget::formLabelAll() + [
      '#default_value' => $this->configuration['label_all'],
    ];

    $form['label_single'] = MultiselectDropdownWidget::formLabelSingle() + [
      '#default_value' => $this->configuration['label_single'],
    ];

    $form['label_plural'] = MultiselectDropdownWidget::formLabelPlural() + [
      '#default_value' => $this->configuration['label_plural'],
    ];

    $form['label_select_all'] = MultiselectDropdownWidget::formLabelSelectAll() + [
      '#default_value' => $this->configuration['label_select_all'],
    ];

    $form['label_select_none'] = MultiselectDropdownWidget::formLabelSelectNone() + [
      '#default_value' => $this->configuration['label_select_none'],
    ];

    $form['label_close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close Button'),
      '#description' => $this->t('The label of the close button. May be omitted.<br>The close button will always generate with an <code>aria-label</code> even if the label is omitted.'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['label_close'],
    ];

    $form['label_submit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit Button'),
      '#description' => $this->t('The label of the submit button. Leave blank to omit.'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['label_submit'],
    ];

    $form['label_clear'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clear Button'),
      '#description' => $this->t('The label of the clear button. Leave blank to omit.'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['label_clear'],
    ];

    $form['search_title'] = MultiselectDropdownWidget::formSearchTitle() + [
      '#default_value' => $this->configuration['search_title'],
    ];

    $form['search_title_display'] = MultiselectDropdownWidget::formSearchTitleDisplay(':input[name="exposed_form_options[bef][filter][' . $this->handler->options['id'] . '][configuration][search_title]"]') + [
      '#default_value' => $this->configuration['search_title_display'],
    ];

    $form['search_placeholder'] = MultiselectDropdownWidget::formSearchPlaceholder(':input[name="exposed_form_options[bef][filter][' . $this->handler->options['id'] . '][configuration][search_title]"]') + [
      '#default_value' => $this->configuration['search_placeholder'],
    ];

    $form['search_character_threshold'] = MultiselectDropdownWidget::formSearchCharacterThreshold(':input[name="exposed_form_options[bef][filter][' . $this->handler->options['id'] . '][configuration][search_title]"]') + [
      '#default_value' => $this->configuration['search_character_threshold'],
    ];

    $form['modal_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Open Dialog As...'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['modal_type'],
    ];
    foreach (ModalType::cases() as $case) {
      $form['modal_type']['#options'][$case->value] = $case->label();
      $form['modal_type'][$case->value] = ['#description' => $case->description()];
    }

    $form['modal_breakpoint'] = [
      '#type' => 'number',
      '#title' => $this->t('Modal Breakpoint'),
      '#description' => $this->t('Screen width at which or below the multiselect dropdown dialog should become modal, disallowing interaction with content outside the dialog until the dialog is closed.'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['modal_breakpoint'],
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][filter][' . $this->handler->options['id'] . '][configuration][modal_type]"]' => [
            'value' => ModalType::Breakpoint->value,
          ],
        ],
        'required' => [
          ':input[name="exposed_form_options[bef][filter][' . $this->handler->options['id'] . '][configuration][modal_type]"]' => [
            'value' => ModalType::Breakpoint->value,
          ],
        ],
      ],
    ];

    $form['default_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default Open'),
      '#description' => $this->t('Whether the dialog should default to open.<br><strong>Note:</strong> Dialogs can only open on page load as non-modals, so use with caution.'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['default_open'],
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][filter][' . $this->handler->options['id'] . '][configuration][modal_type]"]' => [
            'value' => ModalType::Dialog->value,
          ],
        ],
      ],
    ];

    $form['persist_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep Open on AJAX Submission'),
      '#description' => $this->t('Keep the dialog open when the form is submitted via AJAX.'),
      '#required' => FALSE,
      '#default_value' => $this->configuration['persist_open'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function exposedFormAlter(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    parent::exposedFormAlter($form, $form_state);

    $field_id = $this->handler->options['is_grouped']
      ? $this->handler->options['group_info']['identifier']
      : $this->handler->options['expose']['identifier'];

    if ($form[$field_id]) {
      unset($form[$field_id]['#size']);
      $form[$field_id]['#type'] = 'multiselect_dropdown';
      $form[$field_id]['#hierarchy'] = $this->handler->options['hierarchy'] ?? FALSE;
      if ($form[$field_id]['#hierarchy']) {
        $form[$field_id]['#options'] = BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
      }
      $form[$field_id]['#label_aria'] = $this->configuration['label_aria'];
      $form[$field_id]['#label_none'] = $this->configuration['label_none'];
      $form[$field_id]['#label_all'] = $this->configuration['label_all'];
      $form[$field_id]['#label_single'] = $this->configuration['label_single'];
      $form[$field_id]['#label_plural'] = $this->configuration['label_plural'];
      $form[$field_id]['#label_select_all'] = $this->configuration['label_select_all'];
      $form[$field_id]['#label_select_none'] = $this->configuration['label_select_none'];
      $form[$field_id]['#label_close'] = $this->configuration['label_close'];
      $form[$field_id]['#label_submit'] = $this->configuration['label_submit'];
      $form[$field_id]['#label_clear'] = $this->configuration['label_clear'];
      $form[$field_id]['#search_title'] = $this->configuration['search_title'];
      $form[$field_id]['#search_title_display'] = $this->configuration['search_title_display'];
      $form[$field_id]['#search_placeholder'] = $this->configuration['search_placeholder'];
      $form[$field_id]['#search_character_threshold'] = $this->configuration['search_character_threshold'];
      $form[$field_id]['#modal_breakpoint'] = match ($this->configuration['modal_type']) {
        ModalType::Breakpoint->value => (int) $this->configuration['modal_breakpoint'],
        default => $this->configuration['modal_type'],
      };
      $form[$field_id]['#default_open'] = (bool) $this->configuration['default_open'];
      $form[$field_id]['#persist_open'] = (bool) $this->configuration['persist_open'];
      $form[$field_id]['#attached']['library'][] = 'multiselect_dropdown/views';
    }
  }

}
