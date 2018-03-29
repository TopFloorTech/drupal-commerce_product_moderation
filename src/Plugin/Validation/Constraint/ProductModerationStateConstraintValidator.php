<?php

namespace Drupal\commerce_product_moderation\Plugin\Validation\Constraint;

use Drupal\content_moderation\Plugin\Validation\Constraint\ModerationStateConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if a moderation state transition is valid.
 */
class ProductModerationStateConstraintValidator extends ModerationStateConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

}
