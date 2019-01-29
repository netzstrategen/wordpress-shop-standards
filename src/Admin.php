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
   * Plugin configuration settings.
   *
   * @var array
   */
  public static $pluginSettings;

  /**
   * Plugin backend initialization method.
   *
   * @implements admin_init
   */
  public static function init() {
    // Ensures new product are saved before updating its meta data.
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::saveNewProductBeforeMetaUpdate', 1);

    // Updates product delivery time with the lowest delivery time between its own variations.
    add_action('updated_post_meta', __CLASS__ . '::updateProductDeliveryTime', 10, 3);
    // Updates sale percentage when regular price or sale price are updated.
    add_action('updated_post_meta', __CLASS__ . '::updateSalePercentage', 10, 4);

    // Adds custom fields for single products.
    add_action('woocommerce_product_options_general_product_data',  __NAMESPACE__ . '\WooCommerce::woocommerce_product_options_general_product_data');
    // Appends product notes custom field as the last field in the product general options section.
    add_action('woocommerce_product_options_general_product_data', __NAMESPACE__ . '\WooCommerce::productNotesCustomField', 999);
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_process_product_meta');

    // Adds products variations custom fields.
    add_action('woocommerce_product_after_variable_attributes', __NAMESPACE__ . '\WooCommerce::woocommerce_product_after_variable_attributes', 10, 3);
    add_action('woocommerce_save_product_variation', __NAMESPACE__ . '\WooCommerce::woocommerce_save_product_variation', 10, 2);

    // Defines plugin configuration settings.
    if (!isset(static::$pluginSettings)) {
      static::$pluginSettings  = [
        '_set_noindex_products_listings' => [
          'label' => __('Block indexing of products listing pages', Plugin::L10N),
          'description' => __('If checked, noindex meta tags will be added to products listing pages.', Plugin::L10N),
        ],
        '_disable_wpseo_adjacent_rel_links' => [
          'label' => __('Disable Yoast WP Seo adjacent navigation links', Plugin::L10N),
          'description' => __('If checked, injecting of rel prev/next links by plugin WordPress SEO by Yoast will be disabled.', Plugin::L10N),
        ],
      ];
    }

    // Registers plugin settings.
    foreach (static::$pluginSettings as $key => $setting) {
      register_setting(Plugin::L10N . '-settings', Plugin::L10N . $key);
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
      elseif ($product->get_type() === 'simple') {
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

      $sale_percentage = $wpdb->get_var("SELECT FLOOR((regular_price.meta_value - sale_price.meta_value) / regular_price.meta_value * 100) AS sale_percentage
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

  /**
   * Adds a plugin settings option to the admin menu.
   *
   * @implements admin_menu
   */
  public static function admin_menu() {
    $title = __('Shop Standards', Plugin::L10N);
    add_submenu_page('options-general.php', $title, $title, 'manage_options', Plugin::L10N . '-settings', [__CLASS__, 'renderSettings']);
  }

  /**
   * Renders the plugin settings form.
   */
  public static function renderSettings() {
    Plugin::renderTemplate(['templates/settings.php']);
  }

}
