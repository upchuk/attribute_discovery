<?php

namespace Drupal\attribute_discovery\Plugin\Discovery;

use Drupal\attribute_discovery\Plugin\Attribute\PluginAttributeInterface;
use Drupal\attribute_discovery\Plugin\Attribute\Translatable;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileCache\FileCacheInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use ReflectionClass;

/**
 * Defines a discovery mechanism to find attribute-defined plugins in PSR-4 namespaces.
 */
class AttributeClassDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The file cache object.
   */
  protected FileCacheInterface $fileCache;

   /**
   * A suffix to append to each PSR-4 directory associated with a base
   * namespace, to form the directories where plugins are found.
   */
  protected string $directorySuffix = '';

  /**
   * A suffix to append to each base namespace, to obtain the namespaces where
   * plugins are found.
   */
  protected string $namespaceSuffix = '';

  /**
   * A list of base namespaces with their PSR-4 directories.
   */
  protected \Traversable $rootNamespacesIterator;

  /**
   * The name of the attribute that contains the plugin definition.
   *
   * The class corresponding to this name must implement
   * \Drupal\attribute_discovery\Plugin\Attribute\PluginAttributeInterface.
   */
  protected string $pluginDefinitionAttributeName;

  /**
   * Constructs a new instance.
   *
   * @param string $subdir
   *   The plugin's subdirectory, for example 'Plugin/views/filter'
   * @param \Traversable $root_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param string $plugin_definition_attribute_name
   *   (optional) The name of the attribute that contains the plugin definition.
   *   Defaults to 'Drupal\attribute_discovery\Plugin\Attribute\PluginAttribute'.
   */
  public function __construct(string $subdir, \Traversable $root_namespaces, string $plugin_definition_attribute_name = 'Drupal\attribute_discovery\Plugin\Attribute\PluginAttribute') {
    if ($subdir) {
      // Prepend a directory separator to $subdir,
      // if it does not already have one.
      if ('/' !== $subdir[0]) {
        $subdir = '/' . $subdir;
      }
      $this->directorySuffix = $subdir;
      $this->namespaceSuffix = str_replace('/', '\\', $subdir);
    }
    $this->rootNamespacesIterator = $root_namespaces;
    $this->pluginDefinitionAttributeName = $plugin_definition_attribute_name;
    $file_cache_suffix = str_replace('\\', '_', $plugin_definition_attribute_name);
    $this->fileCache = FileCacheFactory::get('attribute_discovery:' . $file_cache_suffix);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = [];

    // Search for classes within all PSR-4 namespace locations.
    foreach ($this->getPluginNamespaces() as $namespace => $dirs) {
      foreach ($dirs as $dir) {
        if (file_exists($dir)) {
          $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
          );
          foreach ($iterator as $fileinfo) {
            if ($fileinfo->getExtension() == 'php') {
              if ($cached = $this->fileCache->get($fileinfo->getPathName())) {
                if (isset($cached['id'])) {
                  // Explicitly unserialize this to create a new object instance.
                  $definitions[$cached['id']] = unserialize($cached['content']);
                }
                continue;
              }

              $sub_path = $iterator->getSubIterator()->getSubPath();
              $sub_path = $sub_path ? str_replace(DIRECTORY_SEPARATOR, '\\', $sub_path) . '\\' : '';
              $class = $namespace . '\\' . $sub_path . $fileinfo->getBasename('.php');

              $reflection = new ReflectionClass($class);
              $attribute = $this->getAttributeOfInstance($reflection, PluginAttributeInterface::class);
              if (!$attribute instanceof \ReflectionAttribute) {
                // Store a NULL object, so the file is not reparsed again.
                $this->fileCache->set($fileinfo->getPathName(), [NULL]);
                continue;
              }

              // We expect a single attribute of this kind.
              $instance = $attribute->newInstance();
              if (!$instance instanceof $this->pluginDefinitionAttributeName) {
                $this->fileCache->set($fileinfo->getPathName(), [NULL]);
                continue;
              }

              $id = $instance->getId();
              $content = $instance->getDefinition();

              // Check to see if we also have a Translatable attribute that
              // can indicate which can process the translatable parts of the
              // plugin definition.
              $attribute = $this->getAttributeOfInstance($reflection, Translatable::class);
              if ($attribute instanceof \ReflectionAttribute) {
                $translatable = $attribute->newInstance();
                $content = $translatable->processDefinition($content);
              }
              $content['class'] = $class;
              $content['provider'] = $this->getProviderFromNamespace($class);
              $definitions[$id] = $content;
              // Explicitly serialize this to create a new object instance.
              $this->fileCache->set($fileinfo->getPathName(), ['id' => $id, 'content' => serialize($content)]);
            }
          }
        }
      }
    }

    return $definitions;
  }

  /**
   * Returns an attribute of a given instance or NULL.
   *
   * @param \ReflectionClass $reflection
   *   The reflection class.
   * @param string $instance
   *   The instance the attribute should be of.
   *
   * @return
   *   The attribute instance.
   */
  protected function getAttributeOfInstance(ReflectionClass $reflection, string $instance) {
    $attributes = $reflection->getAttributes($instance, \ReflectionAttribute::IS_INSTANCEOF);
    if (empty($attributes)) {
      return NULL;
    }

    return reset($attributes);
  }

    /**
   * Extracts the provider name from a Drupal namespace.
   *
   * @param string $namespace
   *   The namespace to extract the provider from.
   *
   * @return string|null
   *   The matching provider name, or NULL otherwise.
   */
  protected function getProviderFromNamespace($namespace) {
    preg_match('|^Drupal\\\\(?<provider>[\w]+)\\\\|', $namespace, $matches);

    if (isset($matches['provider'])) {
      return mb_strtolower($matches['provider']);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginNamespaces() {
    $plugin_namespaces = [];
    if ($this->namespaceSuffix) {
      foreach ($this->rootNamespacesIterator as $namespace => $dirs) {
        // Append the namespace suffix to the base namespace, to obtain the
        // plugin namespace; for example, 'Drupal\Views' may become
        // 'Drupal\Views\Plugin\Block'.
        $namespace .= $this->namespaceSuffix;
        foreach ((array) $dirs as $dir) {
          // Append the directory suffix to the PSR-4 base directory, to obtain
          // the directory where plugins are found. For example,
          // DRUPAL_ROOT . '/core/modules/views/src' may become
          // DRUPAL_ROOT . '/core/modules/views/src/Plugin/Block'.
          $plugin_namespaces[$namespace][] = $dir . $this->directorySuffix;
        }
      }
    }
    else {
      // Both the namespace suffix and the directory suffix are empty,
      // so the plugin namespaces and directories are the same as the base
      // directories.
      foreach ($this->rootNamespacesIterator as $namespace => $dirs) {
        $plugin_namespaces[$namespace] = (array) $dirs;
      }
    }

    return $plugin_namespaces;
  }

}
