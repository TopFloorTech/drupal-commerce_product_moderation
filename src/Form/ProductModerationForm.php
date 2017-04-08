<?php

namespace Drupal\commerce_product_moderation\Form;

use Drupal\content_moderation\Form\EntityModerationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The EntityModerationForm provides a simple UI for changing moderation state.
 */
class ProductModerationForm extends EntityModerationForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_product_moderation.moderation_information'),
      $container->get('commerce_product_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_moderation_entity_moderation_form';
  }

}
