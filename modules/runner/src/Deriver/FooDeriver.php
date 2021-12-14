<?php

namespace Drupal\runner\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Deriver class for the Runner plugins.
 */
class FooDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ([1, 2] as $key) {
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['label'] = t('Foo @key', ['@key' => $key]);
    }
    return $this->derivatives;
  }
}
