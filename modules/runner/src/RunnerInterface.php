<?php

namespace Drupal\runner;

/**
 * Interface for runner plugins.
 */
interface RunnerInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
