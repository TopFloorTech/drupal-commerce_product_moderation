<?php

namespace Drupal\commerce_product_moderation;

use Drupal\commerce_product_moderation\Entity\Handler\ProductModerationHandler;
use Drupal\commerce_product_moderation\Entity\Routing\ProductModerationRouteProvider;
use Drupal\commerce_product_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo extends \Drupal\content_moderation\EntityTypeInfo {

  public function __construct(TranslationInterface $translation, ModerationInformationInterface $moderation_information, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, AccountInterface $current_user, StateTransitionValidationInterface $validator)
  {
    $this->moderationHandlers = [
      'commerce_product' => ProductModerationHandler::class
    ];

    parent::__construct($translation, $moderation_information, $entity_type_manager, $bundle_info, $current_user, $validator);
  }

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
      $entity_types['commerce_product'] = $this->addModerationToEntityType($entity_type);
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

  public function getModeratedBundles() {
    $type = $this->entityTypeManager->getDefinition('commerce_product');
    foreach ($this->bundleInfo->getBundleInfo('commerce_product') as $bundleId => $bundle) {
      if ($this->moderationInfo->shouldModerateEntitiesOfBundle($type, $bundleId)) {
        yield ['entity' => 'commerce_product', 'bundle' => $bundleId];
      }
    }
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

        if (isset($form['footer'])) {
          $form['moderation_state']['#group'] = 'footer';
        }

        if (isset($form['meta']['published'])) {
          $form['meta']['published']['#markup'] = $form['moderation_state']['widget'][0]['current']['#markup'];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFormRedirect(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $moderation_info = \Drupal::getContainer()->get('commerce_product_moderation.moderation_information');
    if ($moderation_info->hasPendingRevision($entity) && $entity->hasLinkTemplate('latest-version')) {
      $entity_type_id = $entity->getEntityTypeId();
      $form_state->setRedirect("entity.$entity_type_id.latest_version", [$entity_type_id => $entity->id()]);
    }
  }

}
