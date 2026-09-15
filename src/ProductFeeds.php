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
    $full_path = static::getFullCategoryPath($id);
    if (empty($full_path)) {
      $full_path = $product_type;
    }
    return $full_path;
  }

  /**
   * Returns the full hierarchical category path of a product.
   *
   * Replaces CTX Feed's woo_feed_get_terms_list_hierarchical_order(), which
   * was removed together with the V5 engine in CTX Feed 8.0; calling it there
   * throws and every product is skipped, producing an empty feed. Reproduces
   * the V5 behaviour: the last term returned by get_the_terms() wins and its
   * ancestors are joined with ' > '.
   *
   * @param int $id
   *   Product ID (the parent ID for variations).
   *
   * @return string
   *   Category path, or an empty string if the product has no category.
   */
  private static function getFullCategoryPath($id) {
    $terms = get_the_terms($id, 'product_cat');
    if (!is_array($terms) || empty($terms)) {
      return '';
    }
    $terms = array_values($terms);
    $term = $terms[count($terms) - 1];
    // get_ancestors() walks to the root and stops on a repeated term, so a
    // malformed hierarchy cannot loop and a deep one is never truncated.
    $path = [];
    foreach (array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy')) as $ancestor_id) {
      $ancestor = get_term($ancestor_id, 'product_cat');
      if ($ancestor instanceof \WP_Term) {
        $path[] = $ancestor->name;
      }
    }
    $path[] = $term->name;
    return implode(' > ', $path);
  }

}
