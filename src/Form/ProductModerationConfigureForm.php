<?php

namespace Drupal\commerce_product_moderation\Form;

use Drupal\content_moderation\Form\ContentModerationConfigureForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The content moderation WorkflowType configuration form.
 *
 * @see \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration
 */
class ProductModerationConfigureForm extends ContentModerationConfigureForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_product_moderation.moderation_information'),
      $container->get('entity_type.bundle.info')
    );
  }
}
