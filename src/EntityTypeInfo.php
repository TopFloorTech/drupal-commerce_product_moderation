<?php

namespace Drupal\commerce_product_moderation;

use Drupal\commerce_product_moderation\Entity\Handler\ProductModerationHandler;
use Drupal\commerce_product_moderation\Entity\Routing\ProductModerationRouteProvider;
use Drupal\commerce_product_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo extends \Drupal\content_moderation\EntityTypeInfo {

  /**
   * A keyed array of custom moderation handlers for given entity types.
   *
   * Any entity not specified will use a common default.
   *
   * @var array
   */
  protected $moderationHandlers = [
    'commerce_product' => ProductModerationHandler::class,
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('commerce_product_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_user'),
      $container->get('commerce_product_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeAlter(array &$entity_types) {
    if (isset($entity_types['commerce_product'])) {
      $entity_type = $entity_types['commerce_product'];
      // The ContentModerationState entity type should never be moderated.
      $entity_types['commerce_product'] = $this->addModerationToEntityType($entity_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    $type = $entity->getEntityType();
    $bundle_of = $type->getBundleOf();
    if ($this->currentUser->hasPermission('administer commerce product moderation') && $bundle_of &&
      $this->moderationInfo->canModerateEntitiesOfEntityType($this->entityTypeManager->getDefinition($bundle_of))
    ) {
      $operations['manage-moderation'] = [
        'title' => t('Manage moderation'),
        'weight' => 27,
        'url' => Url::fromRoute("entity.{$type->id()}.moderation", [$entity->getEntityTypeId() => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function entityExtraFieldInfo() {
    $return = [];
    foreach ($this->getModeratedBundles() as $bundle) {
      $return[$bundle['entity']][$bundle['bundle']]['display']['commerce_product_moderation_control'] = [
        'label' => $this->t('Moderation control'),
        'description' => $this->t("Status listing and form for the entity's moderation state."),
        'weight' => -20,
        'visible' => TRUE,
      ];
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if (!$this->moderationInfo->canModerateEntitiesOfEntityType($entity_type)) {
      return [];
    }

    $fields = [];
    $fields['moderation_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moderation state'))
      ->setDescription(t('The moderation state of this product.'))
      ->setComputed(TRUE)
      ->setClass(ModerationStateFieldItemList::class)
      ->setSetting('target_type', 'moderation_state')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'region' => 'hidden',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'moderation_state_product',
        'weight' => 5,
        'settings' => [],
      ])
      ->addConstraint('ProductModerationState', [])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setReadOnly(FALSE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFormRedirect(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $moderation_info = \Drupal::getContainer()->get('commerce_product_moderation.moderation_information');
    if ($moderation_info->hasForwardRevision($entity) && $entity->hasLinkTemplate('latest-version')) {
      $entity_type_id = $entity->getEntityTypeId();
      $form_state->setRedirect("entity.$entity_type_id.latest_version", [$entity_type_id => $entity->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof BundleEntityFormBase) {
      $type = $form_object->getEntity()->getEntityType();
      if ($this->moderationInfo->canModerateEntitiesOfEntityType($type)) {
        $this->entityTypeManager->getHandler($type->getBundleOf(), 'product_moderation')->enforceRevisionsBundleFormAlter($form, $form_state, $form_id);
      }
    }
    elseif ($form_object instanceof ContentEntityFormInterface) {
      $entity = $form_object->getEntity();
      if ($this->moderationInfo->isModeratedEntity($entity)) {
        $this->entityTypeManager
          ->getHandler($entity->getEntityTypeId(), 'product_moderation')
          ->enforceRevisionsEntityFormAlter($form, $form_state, $form_id);
        // Submit handler to redirect to the latest version, if available.
        $form['actions']['submit']['#submit'][] = [EntityTypeInfo::class, 'bundleFormRedirect'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addModerationToEntityType(ContentEntityTypeInterface $type) {
    if (!$type->hasHandlerClass('product_moderation')) {
      $handler_class = !empty($this->moderationHandlers[$type->id()]) ? $this->moderationHandlers[$type->id()] : ProductModerationHandler::class;
      $type->setHandlerClass('product_moderation', $handler_class);
    }

    if (!$type->hasLinkTemplate('latest-version') && $type->hasLinkTemplate('canonical')) {
      $type->setLinkTemplate('latest-version', $type->getLinkTemplate('canonical'));
    }

    $providers = $type->getRouteProviderClasses() ?: [];
    if (empty($providers['moderation'])) {
      $providers['moderation'] = ProductModerationRouteProvider::class;
      $type->setHandlerClass('route_provider', $providers);
    }

    return $type;
  }

}
