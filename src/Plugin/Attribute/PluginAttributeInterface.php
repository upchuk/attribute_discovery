<?php

namespace Drupal\attribute_discovery\Plugin\Attribute;

/**
 * Interface for the attribute classes used for Drupal plugins.
 */
interface PluginAttributeInterface {

  /**
   * Gets the value of the entire attribute definition.
   */
  public function getDefinition();

  /**
   * Gets the name of the provider of the annotated class.
   *
   * @return string
   */
  public function getProvider();

  /**
   * Sets the name of the provider of the annotated class.
   *
   * @param string $provider
   */
  public function setProvider(string $provider);

  /**
   * Gets the unique ID for this annotated class.
   *
   * @return string
   */
  public function getId();

  /**
   * Gets the class of the annotated class.
   *
   * @return string
   */
  public function getClass();

  /**
   * Sets the class of the annotated class.
   *
   * @param string $class
   */
  public function setClass(string $class);
}
