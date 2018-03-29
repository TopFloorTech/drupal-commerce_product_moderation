<?php

namespace Drupal\commerce_product_moderation\Form;

use Drupal\content_moderation\Form\ContentModerationConfigureEntityTypesForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring moderation usage on a given entity bundle.
 */
class ProductModerationConfigureEntityTypesForm extends ContentModerationConfigureEntityTypesForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

}
