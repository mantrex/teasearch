<?php

declare(strict_types=1);

namespace Drupal\multiselect_dropdown_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\multiselect_dropdown\ModalType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form with multiselect dropdowns for testing.
 */
class MultiselectDropdownTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFormId(): string {
    return 'multiselect_dropdown_test';
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

    $form['colors'] = [
      '#type' => 'multiselect_dropdown',
      '#title' => $this->t('Colors'),
      '#description' => $this->t('Choose a color.'),
      '#label_none' => $this->t('%d Colors Selected'),
      '#label_all' => $this->t('All Colors Selected'),
      '#label_single' => $this->t('%d Color Selected'),
      '#label_plural' => $this->t('%d Colors Selected'),
      '#label_close' => $this->t('Close Colors'),
      '#label_select_all' => $this->t('All Colors'),
      '#label_select_none' => $this->t('No Colors'),
      '#label_submit' => $this->t('Submit Colors'),
      '#label_clear' => $this->t('Clear Colors'),
      '#search_title' => $this->t('Search Colors'),
      '#search_title_display' => 'before',
      '#search_placeholder' => $this->t('Search Colors Placeholder'),
      '#search_character_threshold' => 3,
      '#modal_breakpoint' => 512,
      '#options' => [
        'red' => $this->t('Red'),
        'redorange' => $this->t('Red-Orange'),
        'orange' => $this->t('Orange'),
        'yellow' => $this->t('Yellow'),
        'green' => $this->t('Green'),
        'blue' => $this->t('Blue'),
        'purple' => $this->t('Purple'),
      ],
      '#default_value' => $this->getRequest()->query->all('colors'),
    ];

    $form['continents'] = [
      '#type' => 'multiselect_dropdown',
      '#title' => $this->t('Continents'),
      '#modal_breakpoint' => ModalType::Dialog->value,
      '#options' => [
        'africa' => $this->t('Africa'),
        'asia' => $this->t('Asia'),
        'antarctica' => $this->t('Antarctica'),
        'australia' => $this->t('Australia'),
        'europe' => $this->t('Europe'),
        'north_america' => $this->t('North America'),
        'south_america' => $this->t('South America'),
      ],
      '#default_value' => $this->getRequest()->query->all('continents'),
    ];

    $form['nested'] = [
      '#type' => 'multiselect_dropdown',
      '#title' => $this->t('Nested'),
      '#modal_breakpoint' => ModalType::Dialog->value,
      '#options' => [
        'A' => $this->t('A'),
        '1' => $this->t('- 1'),
        'a' => $this->t('-- a'),
        'i' => $this->t('--- i'),
        'ii' => $this->t('--- ii'),
        'iii' => $this->t('--- iii'),
        'b' => $this->t('-- b'),
        '2' => $this->t('- 2'),
        'c' => $this->t('-- c'),
        '3' => $this->t('- 3'),
        'B' => $this->t('B'),
        '4' => $this->t('- 4'),
        'd' => $this->t('-- d'),
        'iv' => $this->t('iv'),
        'v' => $this->t('--- v'),
        'e' => $this->t('-- e'),
        '5' => $this->t('- 5'),
        'C' => $this->t('C'),
        '6' => $this->t('- 6'),
        'f' => $this->t('-- f'),
        'vi' => $this->t('--- vi'),
        '7' => $this->t('- 7'),
      ],
      '#default_value' => $this->getRequest()->query->all('nested'),
      'iv' => ['#attributes' => ['data-multiselect-dropdown-depth' => 3]],
    ];

    $form['open'] = [
      '#type' => 'multiselect_dropdown',
      '#title' => $this->t('Open'),
      '#modal_breakpoint' => ModalType::Modal->value,
      '#default_open' => TRUE,
      '#options' => [],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form_state->setMethod(Request::METHOD_GET);
    $form['#cache'] = ['max-age' => 0];
    $form['#after_build'] = ['::afterBuild'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override] public function submitForm(
    array &$form,
    FormStateInterface $form_state,
  ): void {}

  /**
   * Remove internal form values.
   */
  public function afterBuild(
    array $element,
    FormStateInterface $form_state,
  ): array {
    foreach ($form_state->getCleanValueKeys() as $key) {
      unset($element[$key]);
    }
    return $element;
  }

}
