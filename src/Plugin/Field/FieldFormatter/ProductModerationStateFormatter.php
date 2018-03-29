<?php

namespace Drupal\commerce_product_moderation\Plugin\Field\FieldFormatter;

use Drupal\content_moderation\Plugin\Field\FieldFormatter\ContentModerationStateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'content_moderation_state' formatter.
 *
 * @FieldFormatter(
 *   id = "product_moderation_state",
 *   label = @Translation("Product moderation state"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class ProductModerationStateFormatter extends ContentModerationStateFormatter {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

}
