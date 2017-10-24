<?php

namespace Drupal\commerce_product_moderation\Entity\Routing;

use Drupal\content_moderation\Entity\Routing\EntityModerationRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides the moderation configuration routes for config entities.
 */
class ProductModerationRouteProvider extends EntityModerationRouteProvider {

  /**
   * Gets the moderation-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getModerationFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('moderation-form') && $entity_type->getFormClass('moderation')) {
      $entity_type_id = $entity_type->id();

      $route = new Route($entity_type->getLinkTemplate('moderation-form'));

      // @todo Come up with a new permission.
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.moderation",
          '_title' => 'Moderation',
        ])
        ->setRequirement('_permission', 'administer commerce product moderation')
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

}
