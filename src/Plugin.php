<?php

namespace Netzstrategen\ShopStandards;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Prefix for naming.
   *
   * @var string
   */
  const PREFIX = 'shop-standards';

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = self::PREFIX;

  /**
   * Plugin initialization method with the lowest possible priority.
   *
   * @implements init
   */
  public static function preInit() {
    // Enables revisions for product descriptions.
    // WooCommerce registers its post types very early in init with a priority
    // of 5, so we need to register upfront.
    add_filter('woocommerce_register_post_type_product', __NAMESPACE__ . '\WooCommerce::woocommerce_register_post_type_product');

    // Adds strike price (range) labels for variable products, too.
    add_filter('woocommerce_variable_sale_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_variable_sale_price_html', 10, 2);
    add_filter('woocommerce_variable_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_variable_sale_price_html', 10, 2);
  }

  /**
   * Plugin initialization method.
   *
   * @implements init
   */
  public static function init() {
    if (is_admin()) {
      return;
    }

    // Blocks search indexing on search pages.
    add_action('wp_head', __CLASS__ . '::wp_head');
    // Disables Yoast adjacent links.
    add_filter('wpseo_disable_adjacent_rel_links', '__return_true');

    // Removes coupon box from checkout.
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

    // Changes the minimum amount of variations to trigger the AJAX handling.
    add_filter('woocommerce_ajax_variation_threshold', __NAMESPACE__ . '\WooCommerce::woocommerce_ajax_variation_threshold', 10, 2);
  }

  /**
   * Blocks search indexing on search pages.
   *
   * @implements wp_head
   */
  public static function wp_head() {
    if (is_search() || preg_match('@/page/\d+@', $_SERVER['REQUEST_URI'])) {
      echo '<meta name="robots" content="noindex">';
    }
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}
