<?php

declare(strict_types=1);

namespace Drupal\multiselect_dropdown\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a form element consisting of a set of checkboxes in a dropdown.
 *
 * Here is a list of properties that are used during the rendering and form
 * processing of multiselect dropdown form elements, besides those properties
 * documented in \Drupal\Core\Render\Element\Checkboxes and
 * \Drupal\Core\Render\Element\RenderElement:
 * - #label_aria (TranslatableMarkup|string): An aria label added to the toggle
 *   button. Defaults to 'Toggle the list of items'.
 * - #label_none (TranslatableMarkup|string): Toggle button label for when no
 *   options are selected. Defaults to 'No Items Selected'.
 * - #label_all (TranslatableMarkup|string): Toggle button label for when all
 *   options are selected. Defaults to 'All Items'.
 * - #label_close (TranslatableMarkup|string): Close button label. Defaults to
 *   an empty string.
 * - #label_single (TranslatableMarkup|string): Toggle button label for when
 *   only one option is selected. May contain the placeholder '%d', which is
 *   replaced by the number of currently selected items. Defaults to '%d Item
 *   Selected'.
 * - #label_plural (TranslatableMarkup|string): Toggle button label for when
 *   more than one options are selected. May contain the placeholder '%d', which
 *   is replaced by the number of currently selected items. Defaults to '%d
 *   Items Selected'.
 * - #label_submit (TranslatableMarkup|string): Submit button label. Leave empty
 *   to omit a submit button within the multiselect. Defaults to an empty
 *   string.
 * - #label_clear (TranslatableMarkup|string): Clear button label. Leave empty
 *   to omit a clear button within the multiselect. Defaults to an empty string.
 * - #search_title (TranslatableMarkup|string): Search field label. Leave empty
 *   to omit the search field within the multiselect. Defaults to an empty
 *   string.
 * - #search_title_display (string): Display options for the search field title.
 *   See the \Drupal\Core\Render\Element\FormElementBase '#title_options' for
 *   available options. Defaults to 'invisible'.
 * - #search_placeholder (TranslatableMarkup|string): Search field placeholder.
 *   Defaults to an empty string.
 * - #search_character_threshold (int): Character threshold for the search to
 *   match items. Less characters than the threshold entered in the search field
 *   will not filter results. Defaults to 3.
 * - #modal_breakpoint (int|string): Integer screen width at which or below the
 *   multiselect dropdown dialog should become modal, disallowing interaction
 *   with content outside the dialog until the dialog is closed, or the backed
 *   value of \Drupal\multiselect_dropdown\ModalType::Dialog or
 *   \Drupal\multiselect_dropdown\ModalType::Modal. Defaults to 768.
 * - #default_open (bool): Whether the dialog should default to open. Note that
 *   dialogs can only open on page load as non-modals, so use with caution.
 *   Defaults to false.
 * - #options (array): Checkbox items. To nest checkboxes, you may indicate
 *   nesting depth by setting the 'data-multiselect-dropdown-depth' attribute to
 *   a non-zero integer corresponding to the depth. See
 *   \Drupal\Core\Render\Element\Checkboxes for instructions on how to set
 *   properties on a per-option basis.
 *
 * Usage example:
 *
 * @code
 * $form['continents'] = [
 *   '#type' => 'multiselect_dropdown',
 *   '#title' => $this->t('Select Countries'),
 *   '#label_aria' => $this->t('Toggle the list of countries'),
 *   '#label_none' => $this->t('No Countries Selected'),
 *   '#label_all' => $this->t('All Countries'),
 *   '#label_single' => $this->t('%d Country Selected'),
 *   '#label_plural' => $this->t('%d Countries Selected'),
 *   '#label_close' => $this->t('Close'),
 *   '#label_select_all' => $this->t('Select All'),
 *   '#label_select_none' => $this->t('Select None'),
 *   '#label_submit' => $this->t('Submit Countries'),
 *   '#label_clear' => $this->t('Clear Countries'),
 *   '#search_title' => $this->t('Search Countries'),
 *   '#search_title_display' => 'above',
 *   '#search_placeholder' => $this->t('Start typing to search the country list...'),
 *   '#search_character_threshold' => 3,
 *   '#modal_breakpoint' => 768,
 *   '#default_open' => FALSE,
 *   '#options' => [
 *     'europe' => $this->t('Europe'),
 *     'germany' => $this->t('Germany'),
 *     'france' => $this->t('France'),
 *     'north_america' => $this->t('North America'),
 *     'canada' => $this->t('Canada'),
 *     'united_states' => $this->t('United States of America'),
 *   ],
 *   'germany' => ['#attributes' => ['data-multiselect-dropdown-depth', 1],
 *   'france' => ['#attributes' => ['data-multiselect-dropdown-depth', 1],
 *   'canada' => ['#attributes' => ['data-multiselect-dropdown-depth', 1],
 *   'united_states' => ['#attributes' => ['data-multiselect-dropdown-depth', 1],
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 * @see \Drupal\Core\Render\Element\FormElementBase
 * @see \Drupal\Core\Render\Element\RenderElement
 */
#[FormElement('multiselect_dropdown')]
final class MultiselectDropdown extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#input' => TRUE,
      '#process' => [
        [MultiselectDropdown::class, 'processCheckboxes'],
      ],
      '#theme' => ['multiselect_dropdown'],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(
    &$element,
    FormStateInterface $form_state,
    &$complete_form,
  ): array {
    $element = parent::processCheckboxes($element, $form_state, $complete_form);

    if (\count($element['#options'])) {
      foreach ($element['#options'] as $key => $choice) {
        $element[$key]['#attributes']['role'] = 'option';
      }
    }

    return $element;
  }

}
