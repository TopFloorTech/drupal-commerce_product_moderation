<?php

namespace Drupal\commerce_product_moderation\Plugin\Field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * A computed field that provides a content entity's moderation state.
 *
 * It links content entities to a moderation state configuration entity via a
 * moderation state content entity.
 */
class ModerationStateFieldItemList extends \Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList {

  /**
   * Gets the moderation state ID linked to a content entity revision.
   *
   * @return string|null
   *   The moderation state ID linked to a content entity revision.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getModerationStateId() {
    $entity = $this->getEntity();

    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('commerce_product_moderation.moderation_information');
    if (!$moderation_info->shouldModerateEntitiesOfBundle($entity->getEntityType(), $entity->bundle())) {
      return NULL;
    }

    // Existing entities will have a corresponding content_moderation_state
    // entity associated with them.
    if (!$entity->isNew() && $content_moderation_state = $this->loadContentModerationStateRevision($entity)) {
      return $content_moderation_state->moderation_state->value;
    }

    $workflow = $moderation_info->getWorkflowForEntity($entity);
    return $workflow ? $workflow->getTypePlugin()->getInitialState($entity)->id() : NULL;
  }

  /**
   * Load the content moderation state revision associated with an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the content moderation state entity will be loaded from.
   *
   * @return \Drupal\content_moderation\Entity\ContentModerationStateInterface|null
   *   The content_moderation_state revision or FALSE if none exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function loadContentModerationStateRevision(ContentEntityInterface $entity) {
    $moderation_info = \Drupal::service('commerce_product_moderation.moderation_information');
    $content_moderation_storage = \Drupal::entityTypeManager()->getStorage('content_moderation_state');

    $revisions = \Drupal::service('entity.query')->get('content_moderation_state')
      ->condition('content_entity_type_id', $entity->getEntityTypeId())
      ->condition('content_entity_id', $entity->id())
      ->condition('workflow', $moderation_info->getWorkflowForEntity($entity)->id())
      ->execute();

    if (empty($revisions)) {
      return NULL;
    }

    /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface $content_moderation_state */
    $content_moderation_state = $content_moderation_storage->loadRevision(key($revisions));
    if ($entity->getEntityType()->hasKey('langcode')) {
      $langcode = $entity->language()->getId();
      if (!$content_moderation_state->hasTranslation($langcode)) {
        $content_moderation_state->addTranslation($langcode);
      }
      if ($content_moderation_state->language()->getId() !== $langcode) {
        $content_moderation_state = $content_moderation_state->getTranslation($langcode);
      }
    }
    return $content_moderation_state;
  }

  /**
   * Updates the default revision flag and the publishing status of the entity.
   *
   * @param string $moderation_state_id
   *   The ID of the new moderation state.
   */
  protected function updateModeratedEntity($moderation_state_id) {
    $entity = $this->getEntity();

    /** @var \Drupal\content_moderation\ModerationInformationInterface $content_moderation_info */
    $content_moderation_info = \Drupal::service('commerce_product_moderation.moderation_information');
    $workflow = $content_moderation_info->getWorkflowForEntity($entity);

    // Change the entity's default revision flag and the publishing status only
    // if the new workflow state is a valid one.
    if ($workflow && $workflow->getTypePlugin()->hasState($moderation_state_id)) {
      /** @var \Drupal\content_moderation\ContentModerationState $current_state */
      $current_state = $workflow->getTypePlugin()->getState($moderation_state_id);

      // Update publishing status if it can be updated and if it needs updating.
      $published_state = $current_state->isPublishedState();
      if (($entity instanceof EntityPublishedInterface) && $entity->isPublished() !== $published_state) {
        $published_state ? $entity->setPublished() : $entity->setUnpublished();
      }
    }
  }

}
