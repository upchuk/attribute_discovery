<?php

namespace Drupal\runner;

use Drupal\attribute_discovery\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;

/**
 * Runner plugin manager.
 */
class RunnerPluginManager extends DefaultPluginManager {

  /**
   * Constructs a RunnerPluginManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->subdir = 'Plugin/Runner';
    $this->namespaces = $namespaces;
    $this->pluginInterface = '\Drupal\runner\RunnerInterface';
    $this->moduleHandler = $module_handler;
    $this->alterInfo('runner_info');
    $this->setCacheBackend($cache_backend, 'runner_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AttributeClassDiscovery($this->subdir, $this->namespaces, '\Drupal\runner\PluginAttribute\Runner');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }

    return $this->discovery;
  }

}
