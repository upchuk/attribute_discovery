<?php

namespace Drupal\runner;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for runner plugins.
 */
abstract class RunnerPluginBase extends PluginBase implements RunnerInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
