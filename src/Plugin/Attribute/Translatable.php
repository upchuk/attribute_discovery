<?php

namespace Drupal\attribute_discovery\Plugin\Attribute;

use Attribute;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Attribute to handle translatable elements from an attribute plugin.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Translatable {

  /**
   * Constructs a new instance.
   *
   * @param array $translatable
   *   An array keyed by the name of the translatable item and the arguments
   * to pass to the Translatable markup.
   */
  public function __construct(protected array $translatable = []) {}

  /**
   * Turns all the translatable elements into translatable markup.
   *
   * @param array $definition
   *   The definition.
   *
   * @return array
   *   The processed definition.
   */
  public function processDefinition(array $definition): array {
    foreach ($this->translatable as $key => $arguments) {
      $definition[$key] = new TranslatableMarkup($definition[$key], $arguments);
    }

    return $definition;
  }
}
