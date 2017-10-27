<?php

namespace Drupal\commerce_product_moderation\Plugin\WorkflowType;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\Plugin\WorkflowType\ContentModeration;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ContentModerationState;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;
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
 * )
 */
class ProductModeration extends ContentModeration implements ContainerFactoryPluginInterface {

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
