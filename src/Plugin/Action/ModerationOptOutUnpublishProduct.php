<?php

namespace Drupal\commerce_product_moderation\Plugin\Action;

use Drupal\commerce_product\Plugin\Action\UnpublishProduct;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alternate action plugin that can opt-out of modifying moderated entities.
 *
 * @see \Drupal\commerce_product\Plugin\Action\PublishProduct
 */
class ModerationOptOutUnpublishProduct extends UnpublishProduct implements ContainerFactoryPluginInterface {

  /**
   * Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * ModerationOptOutUnpublishNode constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $moderation_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($entity, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $entity */
    if ($entity && $this->moderationInfo->isModeratedEntity($entity)) {
      drupal_set_message($this->t("@bundle @label were skipped as they are under moderation and may not be directly unpublished.", ['@bundle' => $entity->bundle(), '@label' => $entity->getEntityType()->getPluralLabel()]), 'warning');
      $result = AccessResult::forbidden();
      return $return_as_object ? $result : $result->isAllowed();
    }
    return parent::access($entity, $account, $return_as_object);
  }

}
