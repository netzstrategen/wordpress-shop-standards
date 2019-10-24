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
    // Appends product notes custom field as the last field in the product general options section.
    add_action('woocommerce_product_options_general_product_data', __NAMESPACE__ . '\WooCommerce::productNotesCustomField', 999);

    // Adds products variations custom fields.
    add_action('woocommerce_product_after_variable_attributes', __NAMESPACE__ . '\WooCommerce::woocommerce_product_after_variable_attributes', 10, 3);

    // Assigns sale category conditionally on product update.
    if (get_option('_' . Plugin::L10N . '_enable_auto_sale_category_assignment') === 'yes') {
      add_action('woocommerce_update_product', __NAMESPACE__ . '\WooCommerce::woocommerce_update_product');
    }

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

  /**
   * Updates sale percentage when regular price or sale price are updated.
   *
   * The plugin woocommerce-advanced-bulk-edit does not invoke the hook 'save_post'
   * when updating the regular price or sale price, so we need to manually
   * calculate the sale percentage whenever post meta fields were updated.
   *
   * @implements updated_post_meta
   */
  public static function updateSalePercentage($check, $object_id, $meta_key, $meta_value) {
    if ($meta_key === '_regular_price' || $meta_key === '_sale_price') {
      $product = wc_get_product($object_id);
      if ($product->get_type() === 'variation') {
        $parent_id = $product->get_parent_id();
        static::saveSalePercentage($parent_id, get_post($parent_id));
      }
      elseif (in_array($product->get_type(), ['simple', 'variable', 'bundle'])) {
        static::saveSalePercentage($object_id, get_post($object_id));
      }
    }
  }

  /**
   * Creates sale percentage on post save.
   */
  public static function saveSalePercentage($post_id, $post) {
    global $wpdb;

    if ($post->post_type === 'product') {
      $product_has_variation = $wpdb->get_var("SELECT ID from wp_posts WHERE post_type = 'product_variation' AND post_parent = $post_id LIMIT 0,1");
      if ($product_has_variation) {
        $where = "WHERE p.post_type = 'product_variation' AND p.post_parent = $post_id";
      }
      else {
        $where = "WHERE p.post_type = 'product' AND p.ID = $post_id";
      }

      $sale_percentage = (int) $wpdb->get_var("SELECT FLOOR((regular_price.meta_value - sale_price.meta_value) / regular_price.meta_value * 100) AS sale_percentage
        FROM wp_posts p
        LEFT JOIN wp_postmeta regular_price ON regular_price.post_id = p.ID AND regular_price.meta_key = '_regular_price'
        LEFT JOIN wp_postmeta sale_price ON sale_price.post_id = p.ID AND sale_price.meta_key = '_sale_price'
        $where
        ORDER BY sale_percentage ASC
        LIMIT 0,1
      ");

      if (is_null($sale_percentage) || $sale_percentage === 100) {
        $sale_percentage = 0;
      }
      update_post_meta($post_id, '_sale_percentage', $sale_percentage);
    }
  }

}
