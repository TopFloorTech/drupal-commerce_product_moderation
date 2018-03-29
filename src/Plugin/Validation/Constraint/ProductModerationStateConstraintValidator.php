<?php

namespace Drupal\commerce_product_moderation\Plugin\Validation\Constraint;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\Plugin\Validation\Constraint\ModerationStateConstraintValidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Checks if a moderation state transition is valid.
 */
class ProductModerationStateConstraintValidator extends ModerationStateConstraintValidator {

  private $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information)
  {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($entity_type_manager, $moderation_information);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $value->getEntity();

    // Ignore entities that are not subject to moderation anyway.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

    if (!$workflow->getTypePlugin()->hasState($entity->moderation_state->value)) {
      // If the state we are transitioning to doesn't exist, we can't validate
      // the transitions for this entity further.
      $this->context->addViolation($constraint->invalidStateMessage, [
        '%state' => $entity->moderation_state->value,
        '%workflow' => $workflow->label(),
      ]);
      return;
    }

    // If a new state is being set and there is an existing state, validate
    // there is a valid transition between them.
    if (!$entity->isNew() && !$this->isFirstTimeModeration($entity)) {
      $original_entity = $entity;

      // If the state of the original entity doesn't exist on the workflow,
      // we cannot do any further validation of transitions, because none will
      // be setup for a state that doesn't exist. Instead allow any state to
      // take its place.
      if (!$workflow->getTypePlugin()->hasState($original_entity->moderation_state->value)) {
        return;
      }

      $new_state = $workflow->getTypePlugin()->getState($entity->moderation_state->value);
      $original_state = $workflow->getTypePlugin()->getState($original_entity->moderation_state->value);

      if (!$original_state->canTransitionTo($new_state->id())) {
        $this->context->addViolation($constraint->message, [
          '%from' => $original_state->label(),
          '%to' => $new_state->label()
        ]);
      }
    }
  }

}
