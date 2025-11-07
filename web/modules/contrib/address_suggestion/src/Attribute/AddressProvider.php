<?php

namespace Drupal\address_suggestion\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an Address provider item annotation object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AddressProvider extends Plugin {

  /**
   * Constructs a Block attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The administrative label of the block.
   * @param string|null $api
   *   The endpoint API of the plugin.
   * @param bool $nokey
   *   Does the API need key.
   * @param bool|null $login
   *   Does the API use plugin authentication.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?string $api = NULL,
    public readonly ?bool $nokey = FALSE,
    public readonly ?bool $login = NULL,
  ) {}

}
