<?php

namespace Drupal\commerce_product_moderation\Plugin\Validation\Constraint;

use Drupal\content_moderation\Plugin\Validation\Constraint\ModerationStateConstraint;
use Symfony\Component\Validator\Constraint;

/**
 * Verifies that products have a valid moderation state.
 *
 * @Constraint(
 *   id = "ProductModerationState",
 *   label = @Translation("Valid moderation state", context = "Validation")
 * )
 */
class ProductModerationStateConstraint extends ModerationStateConstraint {
}
