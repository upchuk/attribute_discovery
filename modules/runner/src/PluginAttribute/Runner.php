<?php

namespace Drupal\runner\PluginAttribute;

use Attribute;
use Drupal\attribute_discovery\Plugin\Attribute\PluginAttribute;

/**
 * Attribute class for the Runner instances.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Runner extends PluginAttribute {}
