<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Plugin.
 */

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
   * @var string
   */
  private static $baseUrl;

  /**
   * @implements init
   */
  public static function preInit() {
    if (is_admin()) {
      // Enables backend to search across product variation IDs, SKUs and Custom IDs.
      add_filter('posts_search', __NAMESPACE__ . '\WooCommerce::posts_search', 10, 2);
    }

    // Enable revisions for product descriptions.
    // WooCommerce registers its post types very early in init with a priority
    // of 5, so we need to register upfront.
    add_filter('woocommerce_register_post_type_product', __NAMESPACE__ . '\WooCommerce::woocommerce_register_post_type_product');

    // Adds strike price (range) labels for variable products, too.
    add_filter('woocommerce_variable_sale_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_variable_sale_price_html', 10, 2);
    add_filter('woocommerce_variable_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_variable_sale_price_html', 10, 2);
    // Changes sale flash label to display sale percentage.
    add_filter('woocommerce_sale_flash', __NAMESPACE__ . '\WooCommerce::woocommerce_change_sale_to_percentage', 10, 3);
  }

  /**
   * @implements init
   */
  public static function init() {
    if (is_admin()) {
      return;
    }

    add_action('wp_enqueue_scripts', __CLASS__ . '::enqueueAssets', 100);

    // Changes amount of how many variations should be handled via JS instead of AJAX.
    add_filter('woocommerce_ajax_variation_threshold', function($qty, $product) { return 100; }, 10, 2);

    // Removes coupon box from checkout.
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form');

    // Adds backordering with proper status messages for every product whether
    // stock managing is enabled or it's available.
    add_filter('woocommerce_get_availability', __NAMESPACE__ . '\WooCommerce::woocommerce_get_availability', 10, 2);

    // Sorts products for _sale_percentage.
    add_filter('woocommerce_get_catalog_ordering_args', __NAMESPACE__ . '\WooCommerce::woocommerce_get_catalog_ordering_args');

    // Changes number of displayed products.
    add_filter('loop_shop_per_page', __NAMESPACE__ . '\WooCommerce::loop_shop_per_page', 20, 1);
  }

  /**
   * Enqueues styles and scripts.
   *
   * @implements wp_enqueue_scripts
   */
  public static function enqueueAssets() {
    $git_version = static::getGitVersion();
    wp_enqueue_script('shop-standards/scripts', static::getBaseUrl() . '/dist/scripts/main.min.js', ['jquery'], FALSE, TRUE);

    wp_localize_script('shop-standards/scripts', 'placeOrder', [
      'button' => __('Processing order…', static::L10N),
    ]);
  }

  /**
   * Generates a version out of the current commit hash.
   *
   * @return string
   */
  public static function getGitVersion() {
    $git_version = NULL;
    if (is_dir(ABSPATH . '.git')) {
      $ref = trim(file_get_contents(ABSPATH . '.git/HEAD'));
      if (strpos($ref, 'ref:') === 0) {
        $ref = substr($ref, 5);
        if (file_exists(ABSPATH . '.git/' . $ref)) {
          $ref = trim(file_get_contents(ABSPATH . '.git/' . $ref));
        }
        else {
          $ref = substr($ref, 11);
        }
      }
      $git_version = substr($ref, 0, 8);
    }
    return $git_version;
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

  /**
   * The base URL path to this plugin's folder.
   *
   * Uses plugins_url() instead of plugin_dir_url() to avoid a trailing slash.
   */
  public static function getBaseUrl() {
    if (!isset(static::$baseUrl)) {
      static::$baseUrl = plugins_url('', static::getBasePath() . '/custom.php');
    }
    return static::$baseUrl;
  }

  /**
   * The absolute filesystem base path of this plugin.
   *
   * @return string
   */
  public static function getBasePath() {
    return dirname(__DIR__);
  }

}
