<?php

namespace Drupal\commerce_product_moderation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Verifies that products have a valid moderation state.
 *
 * @Constraint(
 *   id = "ProductModerationState",
 *   label = @Translation("Valid moderation state", context = "Validation")
 * )
 */
class ProductModerationStateConstraint extends Constraint {

  public $message = 'Invalid state transition from %from to %to';

}
