<?php

namespace Drupal\commerce_product_moderation\Form;

use Drupal\commerce_product_moderation\Plugin\WorkflowType\ProductModeration;
use Drupal\content_moderation\Form\BundleModerationConfigurationForm;
use Drupal\workflows\WorkflowInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring moderation usage on a given entity bundle.
 */
class ProductBundleModerationConfigurationForm extends BundleModerationConfigurationForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle */
    $bundle = $this->getEntity();
    $bundle_of_entity_type = $this->entityTypeManager->getDefinition($bundle->getEntityType()->getBundleOf());
    /* @var \Drupal\workflows\WorkflowInterface[] $workflows */
    $workflows = $this->entityTypeManager->getStorage('workflow')->loadMultiple();

    $options = array_map(function (WorkflowInterface $workflow) {
      return $workflow->label();
    }, array_filter($workflows, function (WorkflowInterface $workflow) {
      return $workflow->status() && $workflow->getTypePlugin() instanceof ProductModeration;
    }));

    $selected_workflow = array_reduce($workflows, function ($carry, WorkflowInterface $workflow) use ($bundle_of_entity_type, $bundle) {
      $plugin = $workflow->getTypePlugin();
      if ($plugin instanceof ProductModeration && $plugin->appliesToEntityTypeAndBundle($bundle_of_entity_type->id(), $bundle->id())) {
        return $workflow->id();
      }
      return $carry;
    });
    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the workflow to apply'),
      '#default_value' => $selected_workflow,
      '#options' => $options,
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['original_workflow'] = [
      '#type' => 'value',
      '#value' => $selected_workflow,
    ];

    $form['bundle'] = [
      '#type' => 'value',
      '#value' => $bundle->id(),
    ];

    $form['entity_type'] = [
      '#type' => 'value',
      '#value' => $bundle_of_entity_type->id(),
    ];

    return $form;
  }

}
