<?php

declare(strict_types=1);

namespace Drupal\multiselect_dropdown;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Represents a type of modal.
 */
enum ModalType: string {

  case Breakpoint = 'breakpoint';
  case Dialog = 'dialog';
  case Modal = 'modal';

  /**
   * A human-readable label for the type.
   */
  public function label(): TranslatableMarkup {
    return match ($this) {
      ModalType::Breakpoint => t('Breakpoint'),
      ModalType::Dialog => t('Dialog'),
      ModalType::Modal => t('Modal'),
    };
  }

  /**
   * A human-readable description for the type.
   */
  public function description(): TranslatableMarkup {
    return match ($this) {
      ModalType::Breakpoint => t('Do not allow interaction with other page elements when open below screen width.'),
      ModalType::Dialog => t('Allow interaction with other page elements when open.'),
      ModalType::Modal => t('Do not allow interaction with other page elements when open.'),
    };
  }

}
