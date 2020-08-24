<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Admin.
 */

namespace Netzstrategen\ShopStandards;

use Svg\Tag\Ellipse;

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
    // Adds custom fields for single products.
    add_action('woocommerce_product_options_general_product_data', __NAMESPACE__ . '\WooCommerce::woocommerce_product_options_general_product_data');
    add_action('woocommerce_product_options_pricing', __NAMESPACE__ . '\WooCommerce::woocommerce_product_options_pricing', 8);

    // Appends product notes custom field as the last field in the product general options section.
    add_action('woocommerce_product_options_general_product_data', __NAMESPACE__ . '\WooCommerce::productNotesCustomField', 999);

    // Adds products variations custom fields.
    add_action('woocommerce_product_after_variable_attributes', __NAMESPACE__ . '\WooCommerce::woocommerce_product_after_variable_attributes', 10, 3);
    add_action('woocommerce_variation_options_pricing', __NAMESPACE__ . '\WooCommerce::woocommerce_variation_options_pricing', 10, 3);

    // Allow ajax requests to specified functions.
    add_action('wp_ajax_is_existing_gtin', __NAMESPACE__ . '\WooCommerce::wp_ajax_is_existing_gtin');

    // Enqueues admin plugin scripts.
    add_action('admin_enqueue_scripts', __CLASS__ . '::admin_enqueue_scripts');
  }

  /**
   * Enqueues admin plugin scripts.
   *
   * @implements admin_enqueue_scripts
   */
  public static function admin_enqueue_scripts($hook) {
    $git_version = Plugin::getGitVersion();
    global $post;
    if ($hook === 'post-new.php' || $hook === 'post.php') {
      if ($post->post_type === 'product') {
        wp_enqueue_script(Plugin::PREFIX . '_admin', Plugin::getBaseUrl() . '/dist/scripts/admin.min.js', ['jquery'], $git_version, TRUE);
        wp_localize_script(Plugin::PREFIX . '_admin', 'shop_standards_admin', array(
          'product_id' => $post->ID,
          'gtin_error_message' => sprintf(
            __('The entered GTIN <a href="%s">already exists</a>. It must be changed in order to save the product.', Plugin::L10N),
            '{{url}}'
          ),
          'gtin_success_message' => __('The entered GTIN is unique.', Plugin::L10N),
        ));
        wp_enqueue_style(Plugin::PREFIX . '_admin', Plugin::getBaseUrl() . '/dist/styles/admin.min.css');
      }
    }
  }

  /**
   * Updates product delivery time with the lowest delivery time between its own variations.
   *
   * If product has variations, its delivery time should be the lowest one
   * between its own variations.
   *
   * @implements updated_post_meta
   */
  public static function updateProductDeliveryTime($meta_id, $object_id, $meta_key) {
    if ($meta_key !== '_lieferzeit') {
      return;
    }

    $product = wc_get_product($object_id);
    if (!in_array($product->get_type(), ['variation', 'variable'])) {
      return;
    }

    if ($product->is_type('variable')) {
      $parent_product = $product;
    }
    elseif ($product->is_type('variation')) {
      $parent_product = wc_get_product($product->get_parent_id());
    }
    $shortest_delivery_time = static::getProductShortestDeliveryTime($parent_product);
    update_post_meta($parent_product->get_id(), $meta_key, $shortest_delivery_time);
  }

  /**
   * Retrieves the shortest delivery time from the variations of a product.
   *
   * @param \WC_Product $product
   *   Variable product.
   *
   * @return string
   *   Shortest delivery time.
   */
  public static function getProductShortestDeliveryTime(\WC_Product $product): string {
    $variation_deliveries_ranges = [];

    foreach ($product->get_children() as $variation) {
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
    $shortest_delivery_time = key($variation_deliveries_ranges) ?: '';
    return $shortest_delivery_time;
  }

}
