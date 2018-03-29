<?php

namespace Drupal\commerce_product_moderation;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\Transition;

/**
 * Validates whether a certain state transition is allowed.
 */
class StateTransitionValidation extends \Drupal\content_moderation\StateTransitionValidation {

}
