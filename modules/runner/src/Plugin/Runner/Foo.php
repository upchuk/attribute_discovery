<?php

namespace Drupal\runner\Plugin\Runner;

use Drupal\attribute_discovery\Plugin\Attribute\Translatable;
use Drupal\runner\RunnerPluginBase;
use Drupal\runner\PluginAttribute\Runner;

#[Runner([
  'id'=> 'foo',
  'label' => 'Foo',
  'description' => 'This is a test plugin',
]), Translatable(['label' => [], 'description' => []])]
/**
 * Foo plugin.
 */
class Foo extends RunnerPluginBase {

}
