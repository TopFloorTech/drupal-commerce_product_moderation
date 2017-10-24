<?php

namespace Drupal\commerce_product_moderation;

use Drupal\commerce_product_moderation\Form\ProductModerationForm;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations Extends \Drupal\content_moderation\EntityOperations {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_product_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Set the latest revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity to create content_moderation_state entity for.
   */
  protected function setLatestRevision(EntityInterface $entity) {
    // No revisions.
  }

  /**
   * Act on entities being assembled before rendering.
   *
   * This is a hook bridge.
   *
   * @see hook_entity_view()
   * @see EntityFieldManagerInterface::getExtraFields()
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }
    if (!$this->moderationInfo->isLatestRevision($entity)) {
      return;
    }

    $component = $display->getComponent('content_moderation_control');
    if ($component) {
      $build['commerce_product_moderation_control'] = $this->formBuilder->getForm(ProductModerationForm::class, $entity);
      $build['commerce_product_moderation_control']['#weight'] = $component['weight'];
    }
  }

  /**
   * Check if the default revision for the given entity is published.
   *
   * The default revision is the same as the entity retrieved by "default" from
   * the storage handler. If the entity is translated, use the default revision
   * of the same language as the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow being applied to the entity.
   *
   * @return bool
   *   TRUE if the default revision is published. FALSE otherwise.
   */
  protected function isDefaultRevisionPublished(EntityInterface $entity, WorkflowInterface $workflow) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $default_revision = $storage->load($entity->id());

    // Ensure we are comparing the same translation as the current entity.
    if ($default_revision instanceof TranslatableInterface && $default_revision->isTranslatable()) {
      // If there is no translation, then there is no default revision and is
      // therefore not published.
      if (!$default_revision->hasTranslation($entity->language()->getId())) {
        return FALSE;
      }

      $default_revision = $default_revision->getTranslation($entity->language()->getId());
    }

    return $default_revision && $workflow->getState($entity->moderation_state->value)->isPublishedState();
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }

    if ($entity->moderation_state->value) {
      $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
      /** @var \Drupal\content_moderation\ContentModerationState $current_state */
      $current_state = $workflow->getState($entity->moderation_state->value);

      // Fire per-entity-type logic for handling the save process.
      $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'product_moderation')->onPresave($entity, TRUE, $current_state->isPublishedState());
    }
  }
}
