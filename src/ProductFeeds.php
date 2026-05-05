<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\ProductFeeds.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Woo feed customized functionality.
 */
class ProductFeeds {

  /**
   * @implements init
   */
  public static function init() {
    // Filters local category path to get full category path.
    add_filter('woo_feed_filter_product_local_category', __CLASS__ . '::woo_feed_filter_product_local_category_callback', 10, 3);
  }

  /**
   * @implements woo_feed_filter_product_local_category_callback
   */
  public static function woo_feed_filter_product_local_category_callback($product_type, $product, $config) {
    $id = $product->get_id();
    if ($product->is_type('variation')) {
      $id = $product->get_parent_id();
    }
    $full_path = woo_feed_get_terms_list_hierarchical_order($id);
    if (empty($full_path)) {
      $full_path = $product_type;
    }
    return $full_path;
  }

}
