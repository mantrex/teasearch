<?php

declare(strict_types=1);

namespace Drupal\multiselect_dropdown_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\multiselect_dropdown\ModalType;

/**
 * Form with a multiselect dropdown for breakpoint exception testing.
 */
class MultiselectDropdownBreakpointTestFrom extends FormBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFormId(): string {
    return 'multiselect_dropdown_breakpoint_test';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
  ): array {
    $form = [];

    $form['breakpoint'] = [
      '#type' => 'multiselect_dropdown',
      '#title' => $this->t('Breakpoint'),
      '#modal_breakpoint' => ModalType::Breakpoint->value,
      '#options' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {}

}
