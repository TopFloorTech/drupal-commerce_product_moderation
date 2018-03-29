<?php

namespace Drupal\commerce_product_moderation\Plugin\WorkflowType;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModeration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Attaches workflows to product bundles.
 *
 * @WorkflowType(
 *   id = "commerce_product_moderation",
 *   label = @Translation("Product moderation"),
 *   required_states = {
 *     "draft",
 *     "published",
 *   },
 *   forms = {
 *     "configure" = "\Drupal\commerce_product_moderation\Form\ProductModerationConfigureForm",
 *     "state" = "\Drupal\content_moderation\Form\ContentModerationStateForm"
 *   },
 * )
 */
class ProductModeration extends ContentModeration {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

}
