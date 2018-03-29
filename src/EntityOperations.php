<?php

namespace Drupal\commerce_product_moderation;

use Drupal\commerce_product_moderation\Form\ProductModerationForm;
use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Entity\ContentModerationStateInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
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
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return;
    }

    if ($entity->moderation_state->value) {
      $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
      /** @var \Drupal\content_moderation\ContentModerationState $current_state */
      $current_state = $workflow->getTypePlugin()->getState($entity->moderation_state->value);

      // Fire per-entity-type logic for handling the save process.
      $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'product_moderation')->onPresave($entity, TRUE, $current_state->isPublishedState());
    }
  }

  function updateOrCreateFromEntity(EntityInterface $entity)
  {
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    $moderationState = ContentModerationState::loadFromModeratedEntity($entity);

    if (!($moderationState instanceof ContentModerationStateInterface)) {
      $storage = $this->entityTypeManager->getStorage('content_moderation_state');
      $moderationState = $storage->create([
        'content_entity_type_id' => $entity->getEntityTypeId(),
        'content_entity_id' => $entity->id(),
        'langcode' => $entity->language()->getId(),
      ]);
      $moderationState->workflow->target_id = $workflow->id();
    }

    if ($entity->getEntityType()->hasKey('langcode')) {
      $entityLangcode = $entity->language()->getId();
      if (!$moderationState->hasTranslation($entityLangcode)) {
        $moderationState->addTranslation($entityLangcode);
      }

      if ($moderationState->language()->getId() !== $entityLangcode) {
        $moderationState = $moderationState->getTranslation($entityLangcode);
      }
    }

    $moderationStateVal = $entity->moderation_state->value;
    if (!$moderationStateVal) {
      $moderationStateVal = $workflow->getTypePlugin()->getInitialState($entity)->id();
    }

    $moderationState->set('moderation_state', $moderationStateVal);
    ContentModerationState::updateOrCreateFromEntity($moderationState);
  }

  public function entityRevisionDelete(EntityInterface $entity)
  {
    // No revision support in Commerce yet.
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
    if ($this->moderationInfo->isLiveRevision($entity)) {
      return;
    }
    if ($this->moderationInfo->isPendingRevisionAllowed($entity)) {
      return;
    }

    $component = $display->getComponent('content_moderation_control');
    if ($component) {
      $build['commerce_product_moderation_control'] = $this->formBuilder->getForm(ProductModerationForm::class, $entity);
    }
  }

}
