<?php

namespace Drupal\commerce_product_moderation;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\content_moderation\ContentPreprocess;

class ProductPreprocess extends ContentPreprocess
{
  public function preprocessProduct(array &$variables)
  {
    $product = $variables['product_entity'] ?? NULL;

    if (!isset($variables['page'])) {
      $variables['page'] = FALSE;
    }

    if ($product instanceof ProductInterface) {
      $variables['page'] = $variables['page'] || $this->isLatestVersionProductPage($product);
    }
  }

  public function isLatestVersionProductPage(ProductInterface $product)
  {
    return $this->routeMatch->getRouteName() === 'entity.commerce_product.latest_version'
      && ($pageProduct = $this->routeMatch->getParameter('commerce_product'))
      && $pageProduct->id() === $product->id();
  }

}
