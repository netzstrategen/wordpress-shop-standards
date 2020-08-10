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
   * WordPress version.
   *
   * @var string
   */
  public static $version = '';

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

    add_action('parse_request', __CLASS__  . '::parse_request');

    // Allow to filter products by delivery time if woocommerce-german-market plugin is active.
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
    if (in_array('woocommerce-german-market/WooCommerce-German-Market.php', $active_plugins)) {
      // Registers products filter widgets supporting delivery time.
      add_action('widgets_init', __NAMESPACE__ . '\ProductFilters\DeliveryTime::widgets_init');

      if (!is_admin()) {
        // Adds custom query variable to filter products by delivery time.
        add_filter('query_vars', __NAMESPACE__ . '\ProductFilters\DeliveryTime::query_vars');
        add_action('pre_get_posts', __NAMESPACE__ . '\ProductFilters\DeliveryTime::pre_get_posts', 1);
      }
    }
  }

  /**
   * Plugin initialization method.
   *
   * @implements init
   */
  public static function init() {
    if (!static::$version) {
      static::$version = get_bloginfo('version');
    }

    if (function_exists('register_field_group')) {
      acf_add_options_sub_page([
        'page_title' => __('Hide "Add to Cart" button', Plugin::L10N),
        'menu_title' => __('Hide "Add to Cart" button', Plugin::L10N),
        'parent_slug' => 'woocommerce',
      ]);
      Plugin::register_acf();
    }

    // Displays sale price as regular price if custom field is checked.
    add_filter('woocommerce_get_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_get_price_html', 10, 2);
    // Adds strike price (range) labels for variable products, too.
    add_filter('woocommerce_get_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_get_variation_price_html', 10, 2);

    // Ensures new product are saved before updating its meta data.
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::saveNewProductBeforeMetaUpdate', 1);
    // Updates product delivery time with the lowest delivery time between its own variations.
    add_action('updated_post_meta', __NAMESPACE__ . '\Admin::updateProductDeliveryTime', 10, 3);

    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_process_product_meta');
    add_action('woocommerce_save_product_variation', __NAMESPACE__ . '\WooCommerce::woocommerce_save_product_variation', 10, 2);

    // Adds woocommerce specific settings.
    add_filter('woocommerce_get_settings_shop_standards', __NAMESPACE__ . '\WooCommerce::woocommerce_get_settings_shop_standards');

    WooCommerceSaleLabel::init();
    Seo::init();
    WooCommerceSalutation::init();
    WooCommerceCheckout::init();
    PlusProducts::init();

    if (is_admin()) {
      return;
    }

    // Removes coupon box from checkout.
    if (get_option('_' . Plugin::L10N . '_disable_coupon_checkout') === 'yes') {
      remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    }

    // Shows coupon amount on cart totals template.
    add_filter('woocommerce_cart_totals_coupon_label', __NAMESPACE__ . '\WooCommerce::addCouponAmount', 10, 2);

    // Changes the minimum amount of variations to trigger the AJAX handling.
    add_filter('woocommerce_ajax_variation_threshold', __NAMESPACE__ . '\WooCommerce::woocommerce_ajax_variation_threshold', 10, 2);

    // Adds backordering with proper status messages.
    add_filter('woocommerce_get_availability', __NAMESPACE__ . '\WooCommerce::woocommerce_get_availability', 10, 2);

    // Changes number of displayed products.
    add_filter('loop_shop_per_page', __NAMESPACE__ . '\WooCommerce::loop_shop_per_page', 20, 1);

    // Hides Add to Cart button for products that must not be sold online.
    add_filter('woocommerce_is_purchasable', __NAMESPACE__ . '\WooCommerce::is_purchasable', 10, 2);

    // Hides sale percentage label.
    add_filter('sale_percentage_output', __NAMESPACE__ . '\WooCommerce::sale_percentage_output', 10, 3);

    // Hides 'add to cart' button for products from specific categories or brands.
    add_action('wp_head', __NAMESPACE__ . '\WooCommerce::wp_head');
    add_action('wp', __NAMESPACE__ . '\WooCommerce::wp');

    // Displays order notice for products that must not be sold online.
    add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\WooCommerce::woocommerce_single_product_summary');

    // Adds basic information (e.g. weight, SKU, etc.) and product attributes to cart item data.
    add_action('woocommerce_get_item_data', __NAMESPACE__ . '\WooCommerce::woocommerce_get_item_data', 10, 2);

    // Removes SKU from order item name.
    add_filter('woocommerce_email_order_items_args', __NAMESPACE__ . '\WooCommerce::woocommerce_email_order_items_args');

    // Adds product attributes to order emails.
    add_filter('woocommerce_display_item_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_display_item_meta', 10, 3);

    // Adds missing postcode validation for some countries.
    add_filter('woocommerce_validate_postcode', __NAMESPACE__ . '\WooCommerce::woocommerce_validate_postcode', 10, 3);

    if (class_exists('Woocommerce_German_Market')) {
      // Remove delivery time from product name in order emails, added by
      // woocommerce-german-market.
      remove_filter('woocommerce_order_item_name', ['WGM_Template', 'add_delivery_time_to_product_title']);

      // Ensure product details column is wide enough.
      add_filter('woocommerce_email_styles', __NAMESPACE__ . '\WooCommerce::woocommerce_email_styles');

      // Adds back in stock date to delivery time string for simple products and product variants.
      add_filter('woocommerce_de_get_deliverytime_string_label_string', __NAMESPACE__ . '\WooCommerce::woocommerce_de_get_deliverytime_string_label_string', 10, 2);

      // Fixes delivery time is not displayed for variable products on products
      // listing pages and on single product view page until a variation is
      // selected. Issue introduced by WGM version 3.10.
      if (\Woocommerce_German_Market::get_version() >= '3.10') {
        add_filter('wgm_deliverytime_loop', __NAMESPACE__ . '\WooCommerce::wgm_deliverytime_loop', 10, 2);
      }

      // Changes WGM delivery time label for variable products.
      add_filter('woocommerce_de_delivery_time_label_shop', __NAMESPACE__ . '\WooCommerce::addsDeliveryTimeLabelSuffix', 10, 2);
      add_filter('woocommerce_de_avoid_check_same_delivery_time_show_parent', '__return_true');
    }

    // Prefetches DNS entries for particular resources.
    add_filter('wp_resource_hints', __NAMESPACE__ . '\Performance::wp_resource_hints', 10, 2);
    // Preloads scripts and loads styles asynchronously.
    add_action('wp_head', __NAMESPACE__ . '\Performance::wp_head', 1);
    // Loads scripts as deferred or async.
    add_filter('script_loader_tag', __NAMESPACE__ . '\Performance::script_loader_tag', 10, 2);
    // Dequeues unwanted scripts and styles.
    add_action('wp_enqueue_scripts', __NAMESPACE__ . '\Performance::wp_enqueue_scripts', 999);

    // Enqueues plugin scripts and styles.
    add_action('wp_enqueue_scripts', __CLASS__ . '::wp_enqueue_scripts');

    // Adds GTIN product number in schema.org.
    add_filter('woocommerce_structured_data_product', __NAMESPACE__ . '\Seo::getProductGtin');
    // Adds Brand name to schema.org.
    add_filter('woocommerce_structured_data_product', __NAMESPACE__ . '\Seo::getProductBrand');
    // Adds product variation price to schema.org.
    add_filter('woocommerce_structured_data_product_offer', __NAMESPACE__ . '\Seo::getProductVariationPrice', 10, 2);
    // Fixes schema.org prices according to tax settings.
    add_filter('woocommerce_structured_data_product_offer', __NAMESPACE__ . '\Seo::adjustPrice', 10, 2);
    // Fixes product availability in schema.org.
    add_filter('woocommerce_structured_data_product_offer', __NAMESPACE__ . '\Seo::adjustAvailability', 10, 2);

    // Fixes WooCommerce strings translations.
    add_filter('gettext', __NAMESPACE__ . '\WooCommerce::gettext', 10, 3);
  }

  /**
   * Redirects requests to shop page using different letter-casing to canonical shop page path.
   *
   * This is necessary as the archives rewrite rules do not match
   * case-insensitive but match a generic page name instead.
   *
   * @implements parse_request
   */
  public static function parse_request($request) {
    if (!$pagename = $request->query_vars['pagename'] ?? '') {
      return;
    };
    $shop_page_slug = get_post_field('post_name', get_option('woocommerce_shop_page_id'), 'raw');
    // Check if the requested pagename matches the shop page post name
    // but does not contain case sensitive characters to avoid redirect loops.
    if (stripos($pagename, $shop_page_slug) !== FALSE && strpos($pagename, $shop_page_slug) === FALSE) {
      wp_redirect(home_url($shop_page_slug), 301);
      exit;
    };
  }

  /**
   * Registers ACF Fields.
   */
  public static function register_acf() {
    // Hide 'add to cart' button for selected categories.
    register_field_group([
      'key' => 'acf_group_hide_add_to_cart',
      'title' => __('Hide "Add to Cart" button', Plugin::L10N),
      'fields' => [[
        'key' => 'acf_hide_add_to_cart_product_cat',
        'name' => 'acf_hide_add_to_cart_product_cat',
        'label' => __('Product categories', Plugin::L10N),
        'instructions' => __('"Add to Cart" button will be hidden for the selected categories.', Plugin::L10N),
        'type' => 'taxonomy',
        'taxonomy' => 'product_cat',
        'field_type' => 'multi_select',
        'allow_null' => 0,
        'add_term' => 1,
        'return_format' => 'id',
        'multiple' => 0,
      ],
      [
        'key' => 'acf_hide_add_to_cart_product_brand',
        'name' => 'acf_hide_add_to_cart_product_brand',
        'label' => __('Product brands', Plugin::L10N),
        'instructions' => __('"Add to Cart" button will be hidden for the selected brands.', Plugin::L10N),
        'type' => 'taxonomy',
        'taxonomy' => 'product_brand',
        'field_type' => 'multi_select',
        'allow_null' => 0,
        'add_term' => 1,
        'return_format' => 'id',
        'multiple' => 0,
      ],
      [
        'key' => 'acf_hide_add_to_cart_product_notice',
        'name' => 'acf_hide_add_to_cart_product_notice',
        'label' => __('Notice', Plugin::L10N),
        'type' => 'textarea',
        'instructions' => __('Notice to display for products that must not be sold online.', Plugin::L10N),
        'rows' => 4,
        'new_lines' => 'wpautop',
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
   * Enqueues plugin scripts and styles.
   *
   * @implements wp_enqueue_scripts
   */
  public static function wp_enqueue_scripts() {
    $git_version = static::getGitVersion();

    wp_enqueue_script(Plugin::PREFIX, static::getBaseUrl() . '/dist/scripts/main.min.js', ['jquery'], $git_version, TRUE);
    wp_localize_script(Plugin::PREFIX, 'shop_standards_settings', [
      'emailConfirmationEmail' => get_option('_' . Plugin::L10N . '_checkout_email_confirmation_field'),
    ]);
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
