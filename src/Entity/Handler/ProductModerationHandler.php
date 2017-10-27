<?php

namespace Drupal\commerce_product_moderation\Entity\Handler;

use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Customizations for node entities.
 */
class ProductModerationHandler extends ModerationHandler {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * NodeModerationHandler constructor.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_info) {
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('commerce_product_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $default_revision, $published_state) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $entity */
    // Update publishing status if it can be updated and if it needs updating.
    if ($entity->isPublished() !== $published_state) {
      $entity->setPublished($published_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    //$form['revision']['#disabled'] = TRUE;
    //$form['revision']['#default_value'] = TRUE;
    //$form['revision']['#description'] = $this->t('Revisions are required.');
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    /* @var ContentEntityInterface $entity */
    //$entity = $form_state->getFormObject()->getEntity();

    //if ($this->moderationInfo->getWorkflowForEntity($entity)) {
      // Force the revision checkbox on.
      //$form['workflow']['options']['#default_value']['revision'] = 'revision';
      //$form['workflow']['options']['revision']['#disabled'] = TRUE;
    //}
  }

}
