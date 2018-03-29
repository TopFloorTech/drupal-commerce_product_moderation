<?php

namespace Drupal\commerce_product_moderation\Plugin\Field\FieldWidget;

use Drupal\commerce_product_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'moderation_state_default' widget.
 *
 * @FieldWidget(
 *   id = "moderation_state_product",
 *   label = @Translation("Moderation state"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ModerationStateWidget extends \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('commerce_product_moderation.moderation_information'),
      $container->get('commerce_product_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return is_a($field_definition->getClass(), ModerationStateFieldItemList::class, TRUE);
  }

}
