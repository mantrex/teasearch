<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test multiselect dropdown form element JavaScript polyfill.
 */
#[Group('multiselect_dropdown')]
#[CoversFunction('multiselect_dropdown_polyfill_preprocess_multiselect_dropdown')]
final class MultiselectDropdownPolyfillTest extends BrowserTestBase {

  use MultiselectDropdownTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'multiselect_dropdown_polyfill',
    'multiselect_dropdown_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  private string $path = '/multiselect-dropdown/form';

  /**
   * Dropdowns.
   *
   * @var string[]
   */
  private static array $dropdowns = [];

  /**
   * Test that the library is attached and the assets are present.
   */
  public function testAssetsLoad(): void {
    $html = $this->loadPage();
    self::assertStringContainsString('multiselect_dropdown_polyfill/css/multiselect-dropdown-polyfill.css', $html);
    self::assertStringContainsString('multiselect_dropdown_polyfill/node_modules/dialog-polyfill/dist/dialog-polyfill.css', $html);
    self::assertStringContainsString('multiselect_dropdown_polyfill/js/dist/multiselect-dropdown-polyfill.js', $html);
    self::assertStringContainsString('multiselect_dropdown_polyfill/node_modules/dialog-polyfill/dist/dialog-polyfill.esm.js', $html);
  }

}
