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
   * Cron event name for removing outdated back-in-stock product metadata.
   *
   * @var string
   */
  const CRON_EVENT_REMOVE_BACK_IN_STOCK = Plugin::PREFIX . '/remove-past-back-in-stock';

  /**
   * Cron event name for delete orphan variants.
   *
   * @var string
   */
  const CRON_EVENT_ORPHAN_VARIANTS_CLEANUP = Plugin::PREFIX . '/cron/orphan-variants-cleanup';

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
      // Ensures the hook is called with a higher priority than others to work.
      add_action('widgets_init', __NAMESPACE__ . '\ProductFilters\DeliveryTime::widgets_init', 99);

      if (!is_admin()) {
        // Adds custom query variable to filter products by delivery time.
        add_filter('query_vars', __NAMESPACE__ . '\ProductFilters\DeliveryTime::query_vars');
        add_action('pre_get_posts', __NAMESPACE__ . '\ProductFilters\DeliveryTime::pre_get_posts', 1);
      }
    }

    // Hides sale percentage label.
    add_filter('sale_percentage_output', __NAMESPACE__ . '\WooCommerce::sale_percentage_output', 10, 3);
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

    if (function_exists('register_field_group') && function_exists('acf_add_options_sub_page')) {
      acf_add_options_sub_page([
        'page_title' => __('Hide "Add to Cart" button', Plugin::L10N),
        'menu_title' => __('Hide "Add to Cart" button', Plugin::L10N),
        'parent_slug' => 'woocommerce',
      ]);
      Plugin::register_acf();
    }

    // Allows adding multiple products to the cart.
    add_action('wp_loaded', __NAMESPACE__ . '\WooCommerce::add_multiple_products_to_cart', 20);

    // Displays sale price as regular price if custom field is checked.
    add_filter('woocommerce_get_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_get_price_html', 20, 2);
    // Adds strike price (range) labels for variable products, too.
    add_filter('woocommerce_get_price_html', __NAMESPACE__ . '\WooCommerce::woocommerce_get_variation_price_html', 20, 2);
    // Remove "From" text prefixed to prices by B2B Market plugin (starting v1.0.6.1).
    add_filter('bm_original_price_html', __NAMESPACE__ . '\WooCommerce::b2b_remove_prefix', 10, 2);

    // Ensures new product are saved before updating its meta data.
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::saveNewProductBeforeMetaUpdate', 1);
    // Updates product delivery time with the lowest delivery time between its own variations.
    add_action('updated_post_meta', __NAMESPACE__ . '\Admin::updateProductDeliveryTime', 10, 3);

    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_process_product_meta');
    add_action('woocommerce_save_product_variation', __NAMESPACE__ . '\WooCommerce::woocommerce_save_product_variation', 10, 2);

    // Adds woocommerce specific settings.
    add_filter('woocommerce_get_settings_shop_standards', __NAMESPACE__ . '\WooCommerce::woocommerce_get_settings_shop_standards');

    // Replaces the order ID of imported Amazon orders with the custom order ID.
    add_filter('woocommerce_amazon_pa_update_checkout_session_payload', __NAMESPACE__ . '\Amazon::woocommerce_amazon_pa_update_checkout_session_payload', 10, 3);

    // Overrides the shipping method ID and title for imported Amazon orders.
    add_filter('wpla_shipping_service_id_map',  __NAMESPACE__ . '\Amazon::wpla_shipping_service_id_map', 10, 2);
    add_filter('wpla_shipping_service_title_map',  __NAMESPACE__ . '\Amazon::wpla_shipping_service_title_map', 10, 2);

    // Sets instance id for imported Amazon orders.
    add_filter('wpla_shipping_instance_id',  __NAMESPACE__ . '\Amazon::wpla_shipping_instance_id', 10, 3);

    WooCommerce::init();
    WooCommerceSaleLabel::init();
    Seo::init();
    WooCommerceSalutation::init();
    WooCommerceCheckout::init();
    PlusProducts::init();
    ProductsPermalinks::init();
    ProductDefects::init();
    ProductFeeds::init();
    ProductFieldsManager::init();
    WooCommerceShippingPackages::init();

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

    // Hides 'add to cart' button for products from specific categories or brands.
    add_action('wp_head', __NAMESPACE__ . '\WooCommerce::wp_head');
    add_action('wp', __NAMESPACE__ . '\WooCommerce::wp');

    // Adds preload tag for main product image to improve largest contentful paint.
    add_action('wp_head', __NAMESPACE__ . '\WooCommerce::preloadMainProductImage', 1);

    // Displays order notice for products that must not be sold online.
    add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\WooCommerce::woocommerce_single_product_summary');

    // Adds basic information (e.g. weight, SKU, etc.) and product attributes to cart item data.
    add_action('woocommerce_get_item_data', __NAMESPACE__ . '\WooCommerce::woocommerce_get_item_data', 10, 2);

    // Removes SKU from order item name.
    add_filter('woocommerce_email_order_items_args', __NAMESPACE__ . '\WooCommerce::woocommerce_email_order_items_args');

    // Adds delivery time information to an order item.
    add_action('woocommerce_new_order_item', __NAMESPACE__ . '\WooCommerce::add_delivery_time_name_to_order_item', 10, 2);

    // Adds product attributes to order emails.
    add_filter('woocommerce_display_item_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_display_item_meta', 10, 3);

    // Adds missing postcode validation for some countries.
    add_filter('woocommerce_validate_postcode', __NAMESPACE__ . '\WooCommerce::woocommerce_validate_postcode', 10, 3);

    // Track counts of orders and order items for each customer.
    add_action('woocommerce_checkout_create_order', __NAMESPACE__ . '\WooCommerceCheckout::woocommerce_checkout_create_order');

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

    if (function_exists('wc_get_product')) {
      add_action(self::CRON_EVENT_REMOVE_BACK_IN_STOCK, __NAMESPACE__ . '\WooCommerce::cron_remove_back_in_stock');
      if (!wp_next_scheduled(static::CRON_EVENT_REMOVE_BACK_IN_STOCK)) {
        wp_schedule_event(strtotime('03:00:00'), 'daily', self::CRON_EVENT_REMOVE_BACK_IN_STOCK);
      }
    }

    add_action(self::CRON_EVENT_ORPHAN_VARIANTS_CLEANUP, __NAMESPACE__ . '\WooCommerce::cron_orphan_variants_cleanup');
    // Schedules delete orphan variants cron.
    if (!wp_next_scheduled(self::CRON_EVENT_ORPHAN_VARIANTS_CLEANUP)) {
      wp_schedule_event(time(), 'weekly', self::CRON_EVENT_ORPHAN_VARIANTS_CLEANUP);
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
    add_action('elementor/frontend/before_enqueue_scripts', __CLASS__ . '::wp_enqueue_elementor_script');

    // Adds GTIN product number in schema.org.
    add_filter('woocommerce_structured_data_product', __NAMESPACE__ . '\Seo::getProductGtin');
    // Adds Brand name to schema.org.
    // Only if woocommerce-brands is not installed (using priority 20).
    add_filter('woocommerce_structured_data_product', __NAMESPACE__ . '\Seo::getProductBrand', 21);
    // Adds product variation price to schema.org.
    add_filter('woocommerce_structured_data_product_offer', __NAMESPACE__ . '\Seo::getProductVariationPrice', 10, 2);
    // Fixes schema.org prices according to tax settings.
    add_filter('woocommerce_structured_data_product_offer', __NAMESPACE__ . '\Seo::adjustPrice', 10, 2);
    // Fixes product availability in schema.org.
    add_filter('woocommerce_structured_data_product_offer', __NAMESPACE__ . '\Seo::adjustAvailability', 10, 2);

    // Fixes WooCommerce strings translations.
    add_filter('gettext', __NAMESPACE__ . '\WooCommerce::gettext', 10, 3);

    // Disables output of related products if over-ride checkbox is enabled.
    add_action('woocommerce_after_single_product_summary', __NAMESPACE__ . '\WooCommerce::disableRelatedProducts', 0, 2);

    // Removes the suffix '/page/1' from archive URLs.
    add_filter('paginate_links', __CLASS__  . '::paginate_links');

    // Override page title on product attribute pages.
    add_filter('woocommerce_page_title', __NAMESPACE__ . '\ProductAttributePageTitle::woocommerce_page_title');

    // Adds extended order status to order page.
    if (is_plugin_active('woocommerce-moeve/plugin.php')) {
      add_action('woocommerce_order_details_before_order_table', __NAMESPACE__ . '\WooCommerce::woocommerce_order_details_before_order_table');
    }

    // Exclude coupon for producs with custom shopstandards fields marked as exclude.
    add_filter('woocommerce_coupon_get_discount_amount', __NAMESPACE__ . '\WooCommerce::apply_product_shop_standard_field_validations_to_coupon',11,5);

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
    
    acf_add_local_field_group([
      'key' => 'acf_shop_standard_coupon_exclude',
      'title' => __('Shop standard fields to exclude from coupon', Plugin::L10N),
      'fields' => [
          [
              'key' => 'acf_shop_standard_coupon_validation',
              'name' => 'acf_shop_standard_coupon_validation',
              'label' => __('Validate Shop standard field on coupon.',Plugin::L10N),              
              'type' => 'repeater',
              'layout' => 'table',
              'instructions' => __('List of shop standard fields to validate.', Plugin::L10N),
              'button_label' => __('Add validation', Plugin::L10N),
              'sub_fields' => [
                [
                  'key' => 'acf_shop_standard_product_field',
                  'name' => 'acf_shop_standard_product_field',
                  'label' => __('Shop standard product field', Plugin::L10N),
                  'type' => 'select',
                  'choices' => woocommerce::get_product_fields(),
                  'allow_null' => 0,
                  'multiple' => 0,
                  'ui' => 1,
                ],
                [
                  'key' => 'acf_shop_standard_include_or_exclude',
                  'name' => 'acf_shop_standard_include_or_exclude',
                  'label' => __('Coupon validaton for field.', Plugin::L10N),
                  'type' => 'select',
                  'choices' => [
                      'INCLUDE' => 'INCLUDE',
                      'EXCLUDE' => 'EXCLUDE',
                  ],                  
                  'allow_null' => 0,
                  'multiple' => 0,
                  'ui' => 1,
                ],
              ],
          ],
      ],
      'location' => [
          [
              [
                  'param' => 'post_type',
                  'operator' => '==',
                  'value' => 'shop_coupon',
              ],
          ],
      ],      
    ]);
  }

  /**
   * Enqueues plugin scripts and styles.
   *
   * @implements wp_enqueue_scripts
   */
  public static function wp_enqueue_scripts() {
    $git_version = static::getGitVersion();
    $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

    wp_enqueue_style(Plugin::PREFIX, static::getBaseUrl() . '/dist/styles/main' . $suffix . '.css', FALSE, $git_version);
    wp_enqueue_script(Plugin::PREFIX, static::getBaseUrl() . '/dist/scripts/main' . $suffix . '.js', ['jquery'], $git_version, TRUE);
    wp_localize_script(Plugin::PREFIX, 'shop_standards_settings', [
      'emailConfirmationEmail' => get_option('_' . Plugin::L10N . '_checkout_email_confirmation_field'),
    ]);
  }

  public static function wp_enqueue_elementor_script() {
    $git_version = static::getGitVersion();
    wp_enqueue_script(Plugin::PREFIX . '/elementor', static::getBaseUrl() . '/dist/scripts/vendor/elementor.js', ['jquery'], $git_version);
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

  /**
   * Removes the suffix '/page/1' from archive URLs.
   *
   * @return string
   */
  public static function paginate_links($link) {
    if (is_paged()) {
      $link = preg_replace('@/page/1/?(\?|$)@', user_trailingslashit('') . '$1', $link);
    }
    return $link;
  }

}
