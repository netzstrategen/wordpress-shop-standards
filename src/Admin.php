<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Admin.
 */

namespace Netzstrategen\ShopStandards;

use WC_Admin_Settings;

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
    // Adds custom fields for single products.
    add_action('woocommerce_product_options_general_product_data',  __NAMESPACE__ . '\WooCommerce::woocommerce_product_options_general_product_data');
    // Appends product notes custom field as the last field in the product general options section.
    add_action('woocommerce_product_options_general_product_data', __NAMESPACE__ . '\WooCommerce::productNotesCustomField', 999);

    // Adds products variations custom fields.
    add_action('woocommerce_product_after_variable_attributes', __NAMESPACE__ . '\WooCommerce::woocommerce_product_after_variable_attributes', 10, 3);

    // Adds plugin configuration section to WooCommerce products settings section.
    add_action('woocommerce_settings_tabs_array', __CLASS__ . '::woocommerce_settings_tabs_array', 30);
    add_action('woocommerce_settings_shop_standards', __CLASS__ . '::woocommerce_settings_shop_standards');
    add_action('woocommerce_settings_save_shop_standards', __CLASS__ . '::woocommerce_settings_save_shop_standards');
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

  /**
   * Defines plugin configuration settings.
   *
   * @return array
   */
  public static function getSettings(): array {
    if (!isset(static::$pluginSettings)) {
      static::$pluginSettings = [
        [
          'name' => __('SEO settings', Plugin::L10N),
          'type' => 'title',
        ],
        [
          'id' => '_robots_noindex_secondary_product_listings',
          'type' => 'checkbox',
          'name' => __('Index first page of paginated products listings only', Plugin::L10N),
          'desc_tip' => __('If checked, noindex meta tag will be added to paginated products listing pages, starting from the second page.', Plugin::L10N),
        ],
        [
          'id' => '_wpseo_disable_adjacent_rel_links',
          'type' => 'checkbox',
          'name' => __('Disable Yoast SEO adjacent navigation links.', Plugin::L10N),
          'desc_tip' => __('Avoids unwanted rankings of search result URLs as well as paginated listing pages in case the shop\'s product listing is using infinite scrolling or lazy loading to display further products without pagination links.', Plugin::L10N),
        ],
        [
          'id' => Plugin::L10N,
          'type' => 'sectionend',
        ],
        [
          'name' => __('Checkout', Plugin::L10N),
          'type' => 'title',
        ],
        [
          'title' => __('Salutation field', Plugin::L10N),
          'desc' => __('Add salutation field in checkout', Plugin::L10N),
          'id' => '_add_salutation_field',
          'type' => 'checkbox',
          'default'  => 'no',
          'desc_tip' => __('If checked a salutation field will be added to the shipping and billing address fields.', Plugin::L10N),
        ],
        [
          'id' => Plugin::L10N,
          'type' => 'sectionend',
        ],
      ];
    }
    return static::$pluginSettings;
  }

  /**
   * Adds a Shop Standards section tab.
   *
   * @implements woocommerce_settings_tabs_array
   */
  public static function woocommerce_settings_tabs_array(array $tabs): array {
    $tabs['shop_standards'] = __('Shop Standards', Plugin::L10N);
    return $tabs;
  }

  /**
   * Adds settings fields to corresponding WooCommerce settings section.
   *
   * @implements woocommerce_settings_<current_tab>
   */
  public static function woocommerce_settings_shop_standards() {
    $settings = static::getSettings();
    WC_Admin_Settings::output_fields($settings);
  }

  /**
   * Triggers setting save.
   *
   * @implements woocommerce_settings_save_<current_tab>
   */
  public static function woocommerce_settings_save_shop_standards() {
    $settings = static::getSettings();
    WC_Admin_Settings::save_fields($settings);
  }
}
