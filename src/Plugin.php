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
   * @var string
   */
  private static $baseUrl;

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
    if (function_exists('register_field_group')) {
      acf_add_options_sub_page([
        'page_title' => __('Hide "Add to Cart" button', Plugin::L10N),
        'menu_title' => __('Hide "Add to Cart" button', Plugin::L10N),
        'parent_slug' => 'woocommerce',
      ]);
      Plugin::register_acf();
    }

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

    // Adds backordering with proper status messages.
    add_filter('woocommerce_get_availability', __NAMESPACE__ . '\WooCommerce::woocommerce_get_availability', 10, 2);

    // Changes number of displayed products.
    add_filter('loop_shop_per_page', __NAMESPACE__ . '\WooCommerce::loop_shop_per_page', 20, 1);

    // Hides 'add to cart' button for products from specific categories or brands.
    add_action('wp_head', __NAMESPACE__ . '\WooCommerce::wp_head');
    add_action('wp', __NAMESPACE__ . '\WooCommerce::wp');

    // Enqueues plugin scripts.
    add_action('wp_enqueue_scripts', __CLASS__ . '::wp_enqueue_scripts');
  }

  /**
   * Registers ACF Fields.
   */
  public static function register_acf() {
    // Hide 'add to cart' button for selected categories.
    register_field_group([
      'key' => 'acf_group_hide_add_to_cart_product_cat',
      'title' => __('Hide "Add to Cart" button for product categories', Plugin::L10N),
      'fields' => [[
        'key' => 'acf_hide_add_to_cart_product_cat',
        'name' => 'acf_hide_add_to_cart_product_cat',
        'type' => 'taxonomy',
        'taxonomy' => 'product_cat',
        'field_type' => 'multi_select',
        'allow_null' => 0,
        'add_term' => 1,
        'return_format' => 'id',
        'multiple' => 0,
      ]],
      'location' => [[[
        'param' => 'options_page',
        'operator' => '==',
        'value' => 'acf-options-' . sanitize_title(__('Hide "Add to Cart" button', Plugin::L10N)),
      ]]],
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'active' => 1,
    ]);
    // Hide 'add to cart' button for selected brands.
    register_field_group([
      'key' => 'acf_group_hide_add_to_cart_product_brand',
      'title' => __('Hide "Add to Cart" button for product brands', Plugin::L10N),
      'fields' => [[
        'key' => 'acf_hide_add_to_cart_product_brand',
        'name' => 'acf_hide_add_to_cart_product_brand',
        'type' => 'taxonomy',
        'taxonomy' => 'product_brand',
        'field_type' => 'multi_select',
        'allow_null' => 0,
        'add_term' => 1,
        'return_format' => 'id',
        'multiple' => 0,
      ]],
      'location' => [[[
        'param' => 'options_page',
        'operator' => '==',
        'value' => 'acf-options-' . sanitize_title(__('Hide "Add to Cart" button', Plugin::L10N)),
      ]]],
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'active' => 1,
    ]);
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
   * Enqueues plugin scripts.
   *
   * @implements wp_enqueue_scripts
   */
  public static function wp_enqueue_scripts() {
    $git_version = static::getGitVersion();
    wp_enqueue_script(Plugin::PREFIX, static::getBaseUrl() . '/dist/scripts/main.min.js', ['jquery'], $git_version, TRUE);
  }

  /**
   * The base URL path to this plugin's folder.
   *
   * Uses plugins_url() instead of plugin_dir_url() to avoid a trailing slash.
   */
  public static function getBaseUrl() {
    if (!isset(static::$baseUrl)) {
      static::$baseUrl = plugins_url('', static::getBasePath() . '/plugin.php');
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

}