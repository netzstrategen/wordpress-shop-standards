<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Admin.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Administrative back-end functionality.
 */
class Admin {

  /**
   * Plugin backend initialization method.
   *
   * @implements admin_init
   */
  public static function init() {
    // Updates product delivery time with the lowest delivery time between its own variations.
    add_action('updated_post_meta', __CLASS__ . '::updated_post_meta', 10, 3);

    // Adds custom fields for single products.
    add_action('woocommerce_product_options_general_product_data',  __NAMESPACE__ . '\WooCommerce::woocommerce_product_options_general_product_data');
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_process_product_meta');

    // Adds products variations custom fields.
    add_action('woocommerce_product_after_variable_attributes', __NAMESPACE__ . '\WooCommerce::woocommerce_product_after_variable_attributes', 10, 3);
    add_action('woocommerce_save_product_variation', __NAMESPACE__ . '\WooCommerce::woocommerce_save_product_variation', 10, 2);
  }

  /**
   * Updates product delivery time with the lowest delivery time between its own variations.
   *
   * If product has variations, its delivery time should be the lowest one
   * between its own variations.
   *
   * @implements updated_post_meta
   */
  public static function updated_post_meta($meta_id, $object_id, $meta_key) {
    if ($meta_key !== '_lieferzeit') {
      return;
    }

    $product = wc_get_product($object_id);
    if ($product->is_type('variation')) {
      $variation_deliveries_ranges = [];

      $parent_product = wc_get_product($product->get_parent_id());
      foreach ($parent_product->get_children() as $variation) {
        $variation_term_id = get_post_meta($variation, '_lieferzeit', TRUE);
        $variation_term_slug = get_term($variation_term_id)->slug;
        // Matches every digits in the delivery time term slug.
        preg_match('/(\d+)/', $variation_term_slug, $variation_delivery_days);
        array_shift($variation_delivery_days);
        if ($variation_delivery_days) {
          $variation_deliveries_ranges[$variation_term_id] = $variation_delivery_days;
        }
      }

      asort($variation_deliveries_ranges);
      update_post_meta($product->get_parent_id(), $meta_key, array_keys($variation_deliveries_ranges)[0]);
    }
  }

}
