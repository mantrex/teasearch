<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\Nightwatch;

use Drupal\TestSite\TestSetupInterface;

/**
 * Setup file used by TestSiteInstallTestScript.
 */
class MultiselectDropdownInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function setup(): void {
    \Drupal::service('module_installer')->install(['multiselect_dropdown_bef_test'], TRUE);
  }

}
