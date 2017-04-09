<?php

namespace Drupal\commerce_product_moderation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation implements ModerationInformationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Creates a new ModerationInformation instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    return $this->shouldModerateEntitiesOfBundle($entity->getEntityType(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function canModerateEntitiesOfEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->hasHandlerClass('product_moderation');
  }

  /**
   * {@inheritdoc}
   */
  public function shouldModerateEntitiesOfBundle(EntityTypeInterface $entity_type, $bundle) {
    if ($this->canModerateEntitiesOfEntityType($entity_type)) {
      $bundles = $this->bundleInfo->getBundleInfo($entity_type->id());
      return isset($bundles[$bundle]['workflow']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision($entity_type_id, $entity_id) {
    return $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entity_type_id, $entity_id) {
    return $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id) {
    return $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision(ContentEntityInterface $entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasForwardRevision(ContentEntityInterface $entity) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isLiveRevision(ContentEntityInterface $entity) {
    $workflow = $this->getWorkflowForEntity($entity);
    return $this->isLatestRevision($entity)
      && $entity->isDefaultRevision()
      && $entity->moderation_state->value
      && $workflow->getState($entity->moderation_state->value)->isPublishedState();
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowForEntity(ContentEntityInterface $entity) {
    $bundles = $this->bundleInfo->getBundleInfo($entity->getEntityTypeId());
    if (isset($bundles[$entity->bundle()]['workflow'])) {
      return $this->entityTypeManager->getStorage('workflow')->load($bundles[$entity->bundle()]['workflow']);
    };
    return NULL;
  }

}
