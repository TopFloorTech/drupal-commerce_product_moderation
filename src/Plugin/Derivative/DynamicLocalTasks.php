<?php

namespace Drupal\commerce_product_moderation\Plugin\Derivative;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates moderation-related local tasks.
 */
class DynamicLocalTasks extends \Drupal\content_moderation\Plugin\Derivative\DynamicLocalTasks {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

}
