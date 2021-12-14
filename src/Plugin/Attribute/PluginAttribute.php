<?php

namespace Drupal\attribute_discovery\Plugin\Attribute;

use Attribute;
use Drupal\Component\Utility\NestedArray;

/**
 * The default attribute for plugins.
 *
 * Plugin types should extend this rather than use it directly.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PluginAttribute implements PluginAttributeInterface {

  /**
   * The plugin definition read from the class annotation.
   */
  protected array $definition;

  /**
   * Constructs a PluginAttribute object.
   */
  public function __construct($definition) {
    $reflection = new \ReflectionClass($this);
    // Only keep actual default values by ignoring NULL values.
    $defaults = array_filter($reflection->getDefaultProperties(), function ($value) {
      return $value !== NULL;
    });
    $parsed_values = $this->parse($definition);
    $this->definition = NestedArray::mergeDeepArray([$defaults, $parsed_values], TRUE);
  }

  /**
   * Parses an annotation into its definition.
   *
   * @param array $values
   *   The annotation array.
   *
   * @return array
   *   The parsed annotation as a definition.
   */
  protected function parse(array $values) {
    $definitions = [];
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $definitions[$key] = $this->parse($value);
      }
      else {
        $definitions[$key] = $value;
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->definition['provider'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider(string $provider) {
    $this->definition['provider'] = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->definition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->definition['class'];
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->definition['class'] = $class;
  }

}
