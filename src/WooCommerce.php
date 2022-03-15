<?php

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce related functionality.
 */
class WooCommerce {

  const FIELD_MARKETING_FOCUS = '_' . Plugin::PREFIX . '_marketing_focus';
  const FIELD_PRICE_COMPARISON_FOCUS = '_' . Plugin::PREFIX . '_price_comparison_focus';
  const FIELD_HIDE_ADD_TO_CART_BUTTON = '_' . Plugin::PREFIX . '_hide_add_to_cart_button';
  const FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL = '_' . Plugin::PREFIX . '_hide_sale_percentage_flash_label';
  const FIELD_PRICE_LABEL = '_' . Plugin::PREFIX . '_price_label';
  const FIELD_SHOW_SALE_PRICE_ONLY = '_' . Plugin::PREFIX . '_show_sale_price_only';
  const FIELD_ERP_INVENTORY = '_' . Plugin::PREFIX . '_erp_inventory_id';
  const FIELD_GTIN = '_' . Plugin::PREFIX . '_gtin';
  const FIELD_BACK_IN_STOCK_DATE = '_' . Plugin::PREFIX . '_back_in_stock_date';
  const FIELD_DISABLE_RELATED_PRODUCTS = '_' . Plugin::PREFIX . '_disable_related_products';
  const FIELD_PRODUCT_PURCHASING_PRICE = '_' . Plugin::PREFIX . '_purchasing_price';
  const FIELD_PRODUCT_INCOMING_STOCK = '_' . Plugin::PREFIX . '_incoming_stock';

  /**
   * Init module.
   */
  public static function init(): void {
    add_filter(Plugin::PREFIX . '/display_custom_product_fields', __CLASS__ . '::get_product_fields');
  }

  /**
   * Gather all custom product fields including their own label.
   */
  public static function get_product_fields(array $fields = []): array {
    return array_merge($fields, [
      self::FIELD_MARKETING_FOCUS => __('Marketing focus product', Plugin::L10N),
      self::FIELD_PRICE_COMPARISON_FOCUS => __('Price comparison focus product', Plugin::L10N),
      self::FIELD_HIDE_ADD_TO_CART_BUTTON => __('Hide add to cart button', Plugin::L10N),
      self::FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL => __('Hide sale percentage bubble', Plugin::L10N),
      self::FIELD_PRICE_LABEL => __('Custom price label', Plugin::L10N),
      self::FIELD_SHOW_SALE_PRICE_ONLY => __('Display sale price as normal price', Plugin::L10N),
      self::FIELD_ERP_INVENTORY => __('ERP/Inventory ID', Plugin::L10N),
      self::FIELD_GTIN => __('Enter the Global Trade Item Number', Plugin::L10N),
      self::FIELD_BACK_IN_STOCK_DATE => __('Enter the back in stock date', Plugin::L10N),
      self::FIELD_DISABLE_RELATED_PRODUCTS => __('Disable related products', Plugin::L10N),
      self::FIELD_PRODUCT_PURCHASING_PRICE => __('Purchasing Price', Plugin::L10N) . ' (' . get_woocommerce_currency_symbol() . ')',
    ]);
  }

  /**
   * Adds woocommerce specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Coupon settings', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_' . Plugin::L10N . '_disable_coupon_checkout',
      'name' => __('Disable coupon input field on checkout page', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Adds strike price for variable products above regular product price labels.
   *
   * @see https://github.com/woocommerce/woocommerce/issues/16169
   *
   * @implements woocommerce_get_price_html
   */
  public static function woocommerce_get_variation_price_html($price, $product) {
    if (
      $product->get_type() !== 'variable' ||
      get_post_meta($product->get_id(), self::FIELD_SHOW_SALE_PRICE_ONLY, TRUE) === 'yes'
    ) {
      return $price;
    }
    $sale_prices = [
      'min' => $product->get_variation_price('min', TRUE),
      'max' => $product->get_variation_price('max', TRUE),
    ];
    $regular_prices = [
      'min' => $product->get_variation_regular_price('min', TRUE),
      'max' => $product->get_variation_regular_price('max', TRUE),
    ];
    $regular_price = [wc_price($regular_prices['min'])];
    if ($regular_prices['min'] !== $regular_prices['max']) {
      $regular_price[] = wc_price($regular_prices['max']);
    }
    $sale_price = [wc_price($sale_prices['min'])];
    if ($sale_prices['min'] !== $sale_prices['max']) {
      $sale_price[] = wc_price($sale_prices['max']);
    }
    if (($sale_prices['min'] !== $regular_prices['min'] || $sale_prices['max'] !== $regular_prices['max'])) {
      if ($sale_prices['min'] !== $sale_prices['max'] || $regular_prices['min'] !== $regular_prices['max']) {
        $price = '<del>' . implode('-', $regular_price) . '</del> <ins>' . implode('-', $sale_price) . '</ins>';
      }
    }
    return $price;
  }

  /**
   * Remove "From" text prefixed to prices by B2B Market plugin (starting v1.0.6.1).
   *
   * @implements bm_original_price_html
   */
  public static function b2b_remove_prefix($html, $product_id) {
    $html = preg_replace('@<span class="b2b-price-prefix">.[^<]*?</span>@', '', $html);
    return $html;
  }

  /**
   * Enables revisions for product descriptions.
   *
   * @implements woocommerce_register_post_type_product
   */
  public static function woocommerce_register_post_type_product($args) {
    $args['supports'][] = 'revisions';
    return $args;
  }

  /**
   * Shows coupon amount on cart totals template.
   *
   * @implements woocommerce_cart_totals_coupon_label
   */
  public static function addCouponAmount($label, $coupon) {
    $coupon_types = [
      'fixed_cart',
      'percent',
      'store_credit',
    ];

    $discountType = $coupon->get_discount_type();
    if (!in_array($discountType, $coupon_types)) {
      return $label;
    }

    if ($coupon->get_meta('_wjecf_is_auto_coupon') !== 'yes') {
      $label .= '<br/>' . $coupon->get_description();
    }

    $amount = $coupon->get_amount();
    if ($discountType === 'percent') {
      $amount = $amount . '%';
    }
    else {
      $amount = wc_price($amount);
    }

    $label .= ' ' . sprintf(__('Coupon value: %s', Plugin::L10N), wp_strip_all_tags($amount));
    return $label;
  }

  /**
   * Changes the minimum amount of variations to trigger the AJAX handling.
   *
   * In the frontend, if a variable product has more than 20 variations, the
   * data will be loaded by ajax rather than handled inline.
   *
   * @see https://woocommerce.wordpress.com/2015/07/13/improving-the-variations-interface-in-2-4/
   *
   * @implements woocommerce_ajax_variation_threshold
   */
  public static function woocommerce_ajax_variation_threshold($qty, $product) {
    return 100;
  }

  /**
   * Displays product availabitily status messages.
   *
   * Status messages are displayed for every product
   * whether stock managing is enabled or it's available.
   *
   * @implements woocommerce_get_availability
   */
  public static function woocommerce_get_availability($stock, $product) {
    $product->set_manage_stock('yes');

    // If low stock threshold is not set at product level, get global option.
    // Zero is allowed so just check for empty string as in WooCommerce Core.
    // @see https://git.io/Je7ai
    if ($product->is_type('variation')) {
      $parent_product = wc_get_product($product->get_parent_id());
      $low_stock_amount = $parent_product->get_low_stock_amount();
    }
    else {
      $low_stock_amount = $product->get_low_stock_amount();
    }
    if ($low_stock_amount === '') {
      $low_stock_amount = get_option('woocommerce_notify_low_stock_amount');
    }

    // Check global "Stock display format" option is set to show low stock notices.
    $show_low_stock_amount = get_option('woocommerce_stock_format') === 'low_amount';

    // is_in_stock() returns true if stock level is zero but backorders allowed.
    // If stock level below threshold (but above zero), show "Only x in stock".
    // Otherwise, show "In stock" (not shown by WooCommerce by default).
    $product_stock_quantity = $product->get_stock_quantity();
    if ($product->is_in_stock()) {
      $stock['availability'] = __('In stock', 'woocommerce');
      $stock['class'] = 'in-stock';

      if ($show_low_stock_amount && $product_stock_quantity > 0 && $product_stock_quantity <= $low_stock_amount) {
        $stock['availability'] = sprintf(__('Only %s immediately deliverable', Plugin::L10N), $product_stock_quantity);
        $stock['class'] = 'low-stock';
      }
    }

    if ($product->backorders_allowed() && $back_in_stock_date = self::getEarliestBackInStock($product)) {
      $date_string = static::getFormattedBackInStockDateString($back_in_stock_date);
      $stock['availability'] = '<strong>' . sprintf(__('Back in stock %s', Plugin::L10N), $date_string) . '</strong>';
    }

    return $stock;
  }

  /**
   * Cron event callback to remove outdated back-in-stock product metadata.
   */
  public static function cron_remove_back_in_stock() {
    global $wpdb;
    $ids = $wpdb->get_col($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p
      INNER JOIN {$wpdb->postmeta} backinstock ON p.ID = backinstock.post_id
      LEFT JOIN {$wpdb->postmeta} moeve ON p.ID = moeve.post_id AND moeve.meta_key LIKE %s
      WHERE backinstock.meta_key = %s
        AND backinstock.meta_value <= %s
        AND moeve.meta_id IS NULL", [
          '_woocommerce-moeve_id_%',
      self::FIELD_BACK_IN_STOCK_DATE,
          date('Y-m-d'),
    ]));
    foreach ($ids as $id) {
      if ($product = wc_get_product($id)) {
        $product->delete_meta_data(self::FIELD_BACK_IN_STOCK_DATE);
        $product->save();
      }
    }
  }

  /**
   * Changes number of displayed products.
   *
   * @implement loop_shop_per_page
   */
  public static function loop_shop_per_page($cols) {
    return 24;
  }

  /**
   * Hides Add to Cart button for products that must not be sold online.
   */
  public static function is_purchasable($purchasable, $product) {
    $product_id = $product->get_id();
    $hide_add_to_cart = get_post_meta($product_id, self::FIELD_HIDE_ADD_TO_CART_BUTTON, TRUE);
    return !wc_string_to_bool($hide_add_to_cart);
  }

  /**
   * Hides sale percentage label.
   *
   * @param string $output
   *   The sale label HTML output.
   * @param int $salePercentage
   *   The sale percentage value.
   * @param \WP_Product $product
   *   The current WooCommerce product.
   *
   * @return string
   *   The modified sale label HTML output.
   */
  public static function sale_percentage_output($output, $salePercentage, $product) {
    if (get_post_meta($product->get_id(), self::FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL, TRUE) === 'yes') {
      $output = '';
    }
    return $output;
  }

  /**
   * Displays custom fields for single products.
   *
   * @implements woocommerce_product_options_general_product_data
   */
  public static function woocommerce_product_options_general_product_data() {
    if (ProductFieldsManager::show_field(self::FIELD_BACK_IN_STOCK_DATE)) {
      // Back in stock date field.
      echo '<div class="options_group show_if_simple show_if_external">';
      woocommerce_wp_text_input([
        'id'          => self::FIELD_BACK_IN_STOCK_DATE,
        'type'        => 'date',
        'label'       => __('Back in stock date', Plugin::L10N),
        'desc_tip'    => true,
        'description' => self::get_product_fields()[self::FIELD_BACK_IN_STOCK_DATE],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_GTIN)) {
      // GTIN field.
      echo '<div class="options_group show_if_simple show_if_external">';
      woocommerce_wp_text_input([
        'id'          => self::FIELD_GTIN,
        'label'       => __('GTIN', Plugin::L10N),
        'desc_tip'    => 'true',
        'description' => self::get_product_fields()[self::FIELD_GTIN],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_ERP_INVENTORY)) {
      // ERP/Inventory ID field.
      echo '<div class="options_group show_if_simple show_if_external">';
      woocommerce_wp_text_input([
        'id'    => self::FIELD_ERP_INVENTORY,
        'label' => self::get_product_fields()[self::FIELD_ERP_INVENTORY],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_SHOW_SALE_PRICE_ONLY)) {
      // Show `sale price only` checkbox.
      echo '<div class="options_group">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_SHOW_SALE_PRICE_ONLY,
        'label' => self::get_product_fields()[self::FIELD_SHOW_SALE_PRICE_ONLY],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_PRICE_LABEL)) {
      // Custom price label
      echo '<div class="options_group">';
      woocommerce_wp_text_input([
        'id'          => self::FIELD_PRICE_LABEL,
        'label'       => self::get_product_fields()[self::FIELD_PRICE_LABEL],
        'desc_tip'    => 'true',
        'description' => __('The label will only be displayed if "Sale price was displayed as regular price" setting is checked.',
          Plugin::L10N),
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL)) {
      // Hide sale percentage flash label.
      echo '<div class="options_group">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL,
        'label' => self::get_product_fields()[self::FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_HIDE_ADD_TO_CART_BUTTON)) {
      // Hide add to cart button.
      echo '<div class="options_group show_if_simple show_if_external">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_HIDE_ADD_TO_CART_BUTTON,
        'label' => self::get_product_fields()[self::FIELD_HIDE_ADD_TO_CART_BUTTON],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_PRICE_COMPARISON_FOCUS)) {
      // Price comparison focus product.
      echo '<div class="options_group">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_PRICE_COMPARISON_FOCUS,
        'label' => self::get_product_fields()[self::FIELD_PRICE_COMPARISON_FOCUS],
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_MARKETING_FOCUS)) {
      // Marketing Focus Product
      echo '<div class="options_group">';
      woocommerce_wp_checkbox([
        'id' => self::FIELD_MARKETING_FOCUS,
        'label' => self::get_product_fields()[self::FIELD_MARKETING_FOCUS],
      ]);
      echo '</div>';
    }
  }

  /**
   * Displays custom fields for single products on the invenotry tab.
   *
   * @implements woocommerce_product_options_stock_fields
   * @implements woocommerce_product_after_variable_attributes
   */
  public static function showIncomingstock() {
    if (ProductFieldsManager::show_field(self::FIELD_PRODUCT_INCOMING_STOCK)) {
      // Back in stock date field.
      echo '<div class="options_group show_if_simple show_if_external">';
      woocommerce_wp_text_input([
        'id' => self::FIELD_PRODUCT_INCOMING_STOCK,
        'label' => __('Incoming Stock', Plugin::L10N),
        'desc_tip' => 'true',
        'description' => self::get_product_fields()[self::FIELD_PRODUCT_INCOMING_STOCK],
      ]);
      echo '</div>';
    }
  }

  /**
   * Displays custom fields in the Linked Products tab.
   *
   * @implements woocommerce_product_options_related
   */
  public static function woocommerce_product_options_related(): void {
    if (ProductFieldsManager::show_field(self::FIELD_DISABLE_RELATED_PRODUCTS)) {
      echo '<div class="options_group">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_DISABLE_RELATED_PRODUCTS,
        'label' => self::get_product_fields()[self::FIELD_DISABLE_RELATED_PRODUCTS],
      ]);
      echo '</div>';
    }
  }

  /**
   * Adds pricing custom fields.
   *
   * @implements woocommerce_product_options_pricing
   */
  public static function woocommerce_product_options_pricing() {
    if (ProductFieldsManager::show_field(self::FIELD_PRODUCT_PURCHASING_PRICE)) {
      woocommerce_wp_text_input([
        'id' => self::FIELD_PRODUCT_PURCHASING_PRICE,
        'class' => 'wc_input_price short',
        'label' => self::get_product_fields()[self::FIELD_PRODUCT_PURCHASING_PRICE],
      ]);
    }
  }

  /**
   * Adds product notes custom field.
   *
   * This field will be added as the last field in the product
   * general options section.
   *
   * @implements woocommerce_product_options_general_product_data
   */
  public static function productNotesCustomField() {
    echo '<div class="options_group show_if_simple show_if_variable show_if_external">';
    woocommerce_wp_textarea_input([
      'id' => '_' . Plugin::PREFIX . '_product_notes',
      'label' => __('Internal product notes', Plugin::L10N),
      'style' => 'min-height: 120px;',
    ]);
    echo '</div>';
  }

  /**
   * Saves custom fields for simple products.
   *
   * @implements woocommerce_process_product_meta
   */
  public static function woocommerce_process_product_meta($post_id) {
    $custom_fields = [
      self::FIELD_BACK_IN_STOCK_DATE,
      self::FIELD_GTIN,
      self::FIELD_ERP_INVENTORY,
      '_' . Plugin::PREFIX . '_product_notes',
      self::FIELD_PRICE_LABEL,
      self::FIELD_PRODUCT_PURCHASING_PRICE,
      self::FIELD_DISABLE_RELATED_PRODUCTS,
    ];

    foreach ($custom_fields as $field) {
      if (isset($_POST[$field])) {
        if (!is_array($_POST[$field]) && $_POST[$field]) {
          if ($field !== self::FIELD_GTIN) {
            update_post_meta($post_id, $field, $_POST[$field]);
          }
          else {
            $found_duplicate = self::is_existing_gtin($post_id, $_POST[$field]);

            if (!$found_duplicate) {
              // GTIN is only saved if unique.
              update_post_meta($post_id, $field, $_POST[$field]);
            }
            else {
              static::showDuplicateGtinErrorNotice($found_duplicate);
            }
          }
        }
        else {
          delete_post_meta($post_id, $field);
        }
      }
    }

    $custom_fields_checkbox = [
      self::FIELD_SHOW_SALE_PRICE_ONLY,
      self::FIELD_HIDE_ADD_TO_CART_BUTTON,
      self::FIELD_PRICE_COMPARISON_FOCUS,
      self::FIELD_MARKETING_FOCUS,
      self::FIELD_HIDE_SALE_PERCENTAGE_FLASH_LABEL,
      self::FIELD_DISABLE_RELATED_PRODUCTS,
    ];

    foreach ($custom_fields_checkbox as $field) {
      $value = isset($_POST[$field]) && !is_array($_POST[$field]) && wc_string_to_bool($_POST[$field]) ? 'yes' : 'no';
      update_post_meta($post_id, $field, $value);
    }
  }

  /**
   * Ensures new product are saved before updating its meta data.
   *
   * New products are still not saved when updated_post_meta hook is called.
   * Since we can not check if the meta keys were changed before running
   * our custom functions (see updateDeliveryTime),
   * we are forcing the post to be saved before updating the meta keys.
   *
   * @implements woocommerce_process_product_meta
   */
  public static function saveNewProductBeforeMetaUpdate($post_id) {
    $product = wc_get_product($post_id);
    $product->save();
  }

  /*
   * Adds CSS override to wp_head.
   *
   * @implements wp_head
   */
  public static function wp_head() {
    global $post;
    // Hide Add to Cart button for variable products of specific brands.
    // The button is always visible also if no variant is selected.
    // If the product has a specific brand, the style gets overridden to hide the button again.
    if ($post && static::productHasSpecificTaxonomyTerm($post->ID, 'product_brand')) {
      echo '<style>.single_variation_wrap .variations_button { display: none; }</style>';
    }
  }

  /*
   * Adds preload tag for main product image to improve largest contentful paint.
   *
   * @implements wp_head
   */
  public static function preloadMainProductImage() {
    global $post;

    if (!$post || !$post->post_type === 'product') {
      return;
    }

    $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'woocommerce_single');
    if ($image) {
      echo '<link rel="preload" href="' . $image[0] . '" as="image">';
    }
  }

  /**
   * Hides 'add to cart' button for products from specific categories or brands.
   *
   * @implements wp
   */
  public static function wp() {
    if (!is_callable('wc_get_product') || empty($product = wc_get_product())) {
      return;
    }
    $product_id = $product->get_id();
    if (static::productHasSpecificTaxonomyTerm($product_id, 'product_cat') || static::productHasSpecificTaxonomyTerm($product_id, 'product_brand')) {
      // If the product is a variation, to preserve the variation dropdown
      // select, we need to remove the single variation add to cart button.
      if ($product->is_type('variable')) {
        remove_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);
      }
      else {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
      }
    }
  }

  /**
   * Displays order notice for products that must not be sold online.
   */
  public static function woocommerce_single_product_summary() {
    if (function_exists('get_field') && empty($notice = get_field('acf_hide_add_to_cart_product_notice', 'option')) || empty($product = wc_get_product())) {
      return;
    }
    $product_id = $product->get_id();

    if (static::productHasSpecificTaxonomyTerm($product_id, 'product_cat') || static::productHasSpecificTaxonomyTerm($product_id, 'product_brand')) {
      echo '<div class="info-notice">' . $notice . '</div>';
    }
  }

  /**
   * Checks whether a given post has a given term.
   */
  public static function productHasSpecificTaxonomyTerm($post_id, $taxonomy_name) {
    if (function_exists('get_field') && $excluded_terms = get_field('acf_hide_add_to_cart_' . $taxonomy_name, 'option')) {
      return has_term($excluded_terms, $taxonomy_name, $post_id);
    }
  }

  /**
   * Creates custom fields for product variations.
   *
   * @implements woocommerce_product_after_variable_attributes
   */
  public static function woocommerce_product_after_variable_attributes($loop, $variation_id, $variation) {
    // Variation back in stock date field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_back_in_stock_date[' . $loop . ']',
      'type' => 'date',
      'label' => __('Back in stock date', Plugin::L10N),
      'desc_tip' => TRUE,
      'description' => __('Enter the back in stock date', Plugin::L10N),
      'value' => get_post_meta($variation->ID, self::FIELD_BACK_IN_STOCK_DATE, TRUE),
    ]);
    echo '</div>';
    // Variation GTIN field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_gtin[' . $loop . ']',
      'label' => __('GTIN:', Plugin::L10N),
      'placeholder' => __('Enter the Global Trade Item Number', Plugin::L10N),
      'value' => get_post_meta($variation->ID, self::FIELD_GTIN, TRUE),
    ]);
    echo '</div>';
    // Variation ERP/Inventory ID field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_erp_inventory_id[' . $loop . ']',
      'label' => __('ERP/Inventory ID:', Plugin::L10N),
      'value' => get_post_meta($variation->ID, self::FIELD_ERP_INVENTORY, TRUE),
    ]);
    echo '</div>';
    // Variation hide add to cart button.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_' . Plugin::PREFIX . '_hide_add_to_cart_button_' . $variation->ID,
      'label' => __('Hide add to cart button', Plugin::L10N),
      'value' => get_post_meta($variation->ID, self::FIELD_HIDE_ADD_TO_CART_BUTTON, TRUE),
    ]);
    echo '</div>';
    // Insufficient variant images button checkbox.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation->ID,
      'label' => __('Variation has insufficient images', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_' . Plugin::PREFIX . '_insufficient_variant_images', TRUE),
      'description' => __('Allows this product to be identified and possibly be excluded by other processes and plugins (e.g. a custom filter for product feeds). Enabling this option has no effect on the output (by default).', Plugin::L10N),
      'desc_tip' => TRUE,
    ]);
    echo '</div>';

    if (ProductFieldsManager::show_field(self::FIELD_PRICE_COMPARISON_FOCUS)) {
      // Price comparison focus product.
      echo '<div style="clear:both">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_PRICE_COMPARISON_FOCUS,
        'label' => __('Price comparison focus product', Plugin::L10N),
        'value' => get_post_meta($variation->ID,
          self::FIELD_PRICE_COMPARISON_FOCUS, true),
      ]);
      echo '</div>';
    }

    if (ProductFieldsManager::show_field(self::FIELD_MARKETING_FOCUS)) {
      // Marketing Focus Product
      echo '<div style="clear:both">';
      woocommerce_wp_checkbox([
        'id'    => self::FIELD_MARKETING_FOCUS,
        'label' => __('Marketing focus product', Plugin::L10N),
        'value' => get_post_meta($variation->ID, self::FIELD_MARKETING_FOCUS,
          true),
      ]);
      echo '</div>';
    }
  }

  /**
   * Adds pricing custom fields for product variations.
   *
   * @implements woocommerce_variation_options_pricing
   */
  public static function woocommerce_variation_options_pricing($loop, $variation_data, $variation) {
    if (ProductFieldsManager::show_field(self::FIELD_PRODUCT_PURCHASING_PRICE)) {
      woocommerce_wp_text_input([
        'id'            => self::FIELD_PRODUCT_PURCHASING_PRICE.'['.$loop.']',
        'class'         => 'wc_input_price short form-row',
        'wrapper_class' => 'form-row',
        'label'         => self::get_product_fields()[self::FIELD_PRODUCT_PURCHASING_PRICE],
        'value'         => get_post_meta($variation->ID,
          self::FIELD_PRODUCT_PURCHASING_PRICE, true),
      ]);
    }
  }

  /**
   * Saves custom fields for product variations.
   *
   * @implements woocommerce_save_product_variation
   */
   public static function woocommerce_save_product_variation($post_id, $loop) {
    if (!isset($_POST['variable_post_id'])) {
      return;
    }
    $variation_id = $_POST['variable_post_id'][$loop];

    $custom_fields = [
      self::FIELD_BACK_IN_STOCK_DATE,
      self::FIELD_GTIN,
      self::FIELD_ERP_INVENTORY,
      self::FIELD_PRODUCT_PURCHASING_PRICE,
    ];

    foreach ($custom_fields as $field) {
      if (isset($_POST[$field]) && isset($_POST[$field][$loop])) {
        if ($_POST[$field][$loop]) {
          if ($field !== self::FIELD_GTIN) {
            update_post_meta($variation_id, $field, $_POST[$field][$loop]);
          }
          else {
            $found_duplicate = self::is_existing_gtin($variation_id, $_POST[$field][$loop]);

            if (!$found_duplicate) {
              // GTIN is only saved if unique.
              update_post_meta($variation_id, $field, $_POST[$field][$loop]);
            }
            else {
              static::showDuplicateGtinErrorNotice($found_duplicate);
            }
          }
        }
        else {
          delete_post_meta($variation_id, $field);
        }
      }
    }

    // Hide add to cart button.
    $hide_add_to_cart_button = isset($_POST['_' . Plugin::PREFIX . '_hide_add_to_cart_button_' . $variation_id]) && wc_string_to_bool($_POST['_' . Plugin::PREFIX . '_hide_add_to_cart_button_' . $variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, self::FIELD_HIDE_ADD_TO_CART_BUTTON, $hide_add_to_cart_button);

    // Insufficient images checkbox.
    $insufficient_variant_images = isset($_POST['_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id]) && wc_string_to_bool($_POST['_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_' . Plugin::PREFIX . '_insufficient_variant_images', $insufficient_variant_images);

    // Price comparison focus product.
    $price_comparison_focus = isset($_POST[self::FIELD_PRICE_COMPARISON_FOCUS]) && wc_string_to_bool($_POST[self::FIELD_PRICE_COMPARISON_FOCUS]) ? 'yes' : 'no';
    update_post_meta($variation_id, self::FIELD_PRICE_COMPARISON_FOCUS, $price_comparison_focus);

     // Marketing focus product.
     $marketingFocusFieldValue = isset($_POST[self::FIELD_MARKETING_FOCUS]) && wc_string_to_bool($_POST[self::FIELD_MARKETING_FOCUS]) ? 'yes' : 'no';
     update_post_meta($variation_id, self::FIELD_MARKETING_FOCUS, $marketingFocusFieldValue);
  }

  /**
   * Displays sale price as regular price if custom field is checked.
   *
   * @implements woocommerce_get_price_html
   */
  public static function woocommerce_get_price_html($price, $product) {
    $product_id = $product->get_type() === 'variation' ? $product->get_parent_id() : $product->get_id();
    if (get_post_meta($product_id, self::FIELD_SHOW_SALE_PRICE_ONLY, TRUE) === 'yes') {
      if ($product->get_type() === 'variable') {
        if ($product->get_variation_sale_price() === $product->get_variation_regular_price()) {
          return $price;
        }
        $sale_prices = [
          'min' => $product->get_variation_price('min', TRUE),
          'max' => $product->get_variation_price('max', TRUE),
        ];
        $sale_price = [wc_price($sale_prices['min'])];
        if ($sale_prices['min'] !== $sale_prices['max']) {
          $sale_price[] = wc_price($sale_prices['max']);
        }
        $price = implode('-', $sale_price);
      }
      else {
        if (!$product->get_sale_price()) {
          return $price;
        }
        $price = wc_price(wc_get_price_to_display($product)) . $product->get_price_suffix();
      }

      if (is_product() && !static::isSideProduct()) {
        $price_label = get_post_meta($product_id, self::FIELD_PRICE_LABEL, TRUE) ?: __('(Our price)', Plugin::L10N);
        $price .= ' ' . $price_label;
      }
    }
    return $price;
  }

  /**
   * Checks if displayed product is a related, cross-sell or up-sell product.
   *
   * @return bool
   *   TRUE is current displayed product is related, cross-sell or up-sell.
   */
  public static function isSideProduct() {
    global $woocommerce_loop;

    // For the main product in the product single view, $woocommerce_loop['name']
    // value is 'up-sells'. We can only detect it is the main product checking
    // that $woocommerce_loop['loop'] value is 1.
    if (isset($woocommerce_loop['loop']) && $woocommerce_loop['loop'] === 1) {
      return FALSE;
    }

    // At this point we have discarded the main product. Only side products like
    // related, cross-sells and up-sells should remain.
    if (isset($woocommerce_loop['name']) && in_array($woocommerce_loop['name'], ['related', 'cross-sells', 'up-sells'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Adds basic information (e.g. weight, sku, etc.) and product attributes to cart item data.
   *
   * @implements woocommerce_get_item_data
   */
  public static function woocommerce_get_item_data($data, $cartItem) {
    $product = ($cartItem['variation_id']) ? wc_get_product($cartItem['variation_id']) : wc_get_product($cartItem['product_id']);
    // Discard the product variation attributes already included in the parent product data to avoid duplicates.
    $stringified_data = serialize($data);
    $filtered_attributes = array_filter(static::getProductAttributes($product), function ($item) use ($stringified_data) {
      return strpos($stringified_data, '"' . $item['name'] . '"') === FALSE;
    });

    $separator = [[
      'key' => 'separator',
      'value' => '',
    ]];

    // Display delivery time from woocommerce-german-market first for each order item.
    // Adds a separator element before the attributes list of each order item.
    if (count($data) > 1) {
      $found = FALSE;
      for ($pos = 0; $pos < count($data); $pos++) {
        if (in_array(__('Delivery Time', 'woocommerce-german-market'), $data[$pos], TRUE)) {
          $found = TRUE;
          break;
        }
      }
      if ($found) {
        $data = array_merge(array_splice($data, $pos, 1), $separator, $data);
      }
    }
    else {
      $data = array_merge($data, $separator);
    }

    // Add product data (SKU, dimensions and weight) and attributes.
    // Note: we display parent attributes for production variations.
    $product_data_set = array_merge(static::getProductData($product), $data, $filtered_attributes);

    return $product_data_set;
  }

  /**
   * Adds basic information (e.g. weight, sku, etc.) and product attributes to order emails.
   *
   * @param string $html
   *  Rendered list of product meta.
   *
   * @param object $item
   *  The processed item object.
   *
   * @param array $args
   *   The array of formatting arguments.
   *
   * @implements woocommerce_display_item_meta
   */
  public static function woocommerce_display_item_meta($html, $item, $args) {
    $strings = [];
    $product = $item->get_product();
    $data = static::getProductData($product);
    // @todo The separator element is stripped from the sent mail.
    if (!is_wc_endpoint_url()) {
      $data[] = [
        'name' => '',
        'value' => '<hr>',
      ];
    }
    // Add product meta which is contained in $html after product data.
    foreach ($item->get_formatted_meta_data() as $meta_id => $meta) {
      $data[] = [
        'name' => $meta->display_key,
        'value' => strip_tags($meta->display_value),
      ];
    }
    $product_data_set = array_merge($data, static::getProductAttributes($product));

    // Display delivery time from order item meta for each order item.
    $delivery_time = wc_get_order_item_meta($item->get_id(), '_deliverytime');
    $delivery_time = get_term($delivery_time, 'product_delivery_times');
    $delivery_time = $delivery_time->name ?? '';
    // Add the back in stock date next to the delivery time.
    $delivery_time = self::woocommerce_de_get_deliverytime_string_label_string($delivery_time, $product);

    if ($delivery_time) {
      array_splice($product_data_set, 1, 0, [[
        'name' => __('Delivery Time', 'woocommerce-german-market'),
        'value' => $delivery_time,
      ]]);
    }

    foreach ($product_data_set as $productData) {
      $string = NULL;
      if (!empty($productData['name'])) {
        $string .= '<strong class="wc-item-meta-label">' . $productData['name'] . ':</strong> ';
      }
      if (isset($productData['value'])) {
        $string .= $productData['value'];
      }
      if (isset($string)) {
        $strings[] = $string;
      }
    }

    if ($strings) {
      $html = $args['before'] . implode($args['separator'], $strings) . $args['after'];
    }
    return $html;
  }

  /**
   * Removes SKU from order item name, added by woocommerce-german-market.
   *
   * @implements woocommerce_email_order_items_args
   */
  public static function woocommerce_email_order_items_args($args) {
    $args['show_sku'] = FALSE;
    return $args;
  }

  /**
   * Ensures product details column is wide enough.
   *
   * @implements woocommerce_email_styles
   */
  public static function woocommerce_email_styles($css) {
    $css .= '.order_item td:first-child {width: 75%;}';
    return $css;
  }

  /**
   * Retrieves basic data (SKU, dimensions and weight) for a given product.
   *
   * @param WC_Product $product
   *   Product for which data has to be retrieved.
   *
   * @return array
   *   Set of product data including weight, dimensions and SKU.
   */
  public static function getProductData(\WC_Product $product) {
    $product_data = [];
    $product_data_label = [];
    $product_data_value = [];

    // Adds sku to the cart item data.
    if ($sku = $product->get_sku()) {
      $product_data_label[] = __('SKU', 'woocommerce');
      $product_data_value[] = $sku;
    }
    if ($erp_id = get_post_meta($product->get_id(), self::FIELD_ERP_INVENTORY, TRUE)) {
      $product_data_label[] = __('ERP/ID', Plugin::L10N);
      $product_data_value[] = $erp_id;
    }
    if ($product_data_value) {
      $product_data[] = [
        'name' => implode(' | ', $product_data_label),
        'value' => implode(' | ', $product_data_value),
      ];
    }

    // Adds dimensions to the cart item data.
    if ($dimensions_value = array_filter($product->get_dimensions(FALSE))) {
      $product_data[] = [
        'name' => __('Dimensions', 'woocommerce'),
        'value' => wc_format_dimensions($dimensions_value),
      ];
    }

    // Adds weight to the cart item data.
    if ($weight_value = $product->get_weight()) {
      $product_data[] = [
        'name' => __('Weight', 'woocommerce'),
        'value' => $weight_value . ' kg',
      ];
    }

    return apply_filters(Plugin::PREFIX . '_product_data', $product_data);
  }

  /**
   * Retrieves the attributes of a given product.
   *
   * @param WC_Product $product
   *   Product for which attributes should be retrieved.
   *
   * @return array
   *   List of attributes of the product.
   */
  public static function getProductAttributes(\WC_Product $product) {
    $data = [];
    if ($parent_id = $product->get_parent_id()) {
      $product = wc_get_product($parent_id);
    }

    if ($product->get_type() === 'variable') {
      $variation_attributes = $product->get_variation_attributes();
    }
    else {
      $variation_attributes = [];
    }
    $attributes = $product->get_attributes();

    foreach ($attributes as $key => $attribute) {
      // Skips variation selected attributes to avoid displaying duplicates.
      if (isset($variation_attributes[$key])) {
        continue;
      }
      if ($attribute['is_taxonomy'] && $attribute['is_visible'] === 1) {
        $terms = wp_get_post_terms($product->get_id(), $attribute['name'], 'all');
        if (empty($terms)) {
          continue;
        }

        $taxonomy = $terms[0]->taxonomy;
        $taxonomy_object = get_taxonomy($taxonomy);
        $taxonomy_label = '';
        if (isset($taxonomy_object->labels->name)) {
          $taxonomy_label = str_replace(__('Product ', Plugin::L10N), '', $taxonomy_object->labels->name);
        }

        $excluded_taxonomies = apply_filters(Plugin::PREFIX . '/product_attributes/excluded_taxonomies', []);
        if (in_array($taxonomy_label, $excluded_taxonomies)) {
          continue;
        }

        $data[] = [
          'name' => $taxonomy_label,
          'value' => implode(', ', wp_list_pluck($terms, 'name')),
        ];
      }
    }
    return $data;
  }

  /**
   * Adds missing postcode validation for some countries.
   *
   * @implements woocommerce_validate_postcode
   */
  public static function woocommerce_validate_postcode($valid, $postcode, $country) {
    switch ($country) {
      case 'DK':
      case 'BE':
      case 'LU':
      case 'CH':
        $valid = (bool) preg_match('@^\d{4}$@', $postcode);
        break;

      case 'LI':
        $valid = (bool) preg_match('@^((948[5-9])|(949[0-8]))$@', $postcode);
        break;

      case 'NL':
        $valid = (bool) preg_match('@\d{4} ?[A-Z]{2}@', $postcode);
        break;
    }
    return $valid;
  }

  /**
   * Prepares taxonomy terms as select field options.
   */
  public static function getTaxonomyTermsAsSelectOptions($taxonomy, array $args = []) {
    $terms = get_terms(array_merge([
      'taxonomy' => $taxonomy,
      'fields' => 'id=>name',
      'hide_empty' => false,
    ], $args));
    return is_wp_error($terms) ? [] : $terms;
  }

  /**
   * Returns the entire list of registered product attributes.
   *
   * @return array
   */
  public static function getAvailableAttributes(): array {
    $attributes = [];
    foreach (wc_get_attribute_taxonomies() as $attr) {
      $attributes[$attr->attribute_name] = $attr->attribute_label;
    }
    asort($attributes);
    return $attributes;
  }

  /**
   * Retrieves the earliest "back in stock" date from a product or its variations.
   * If there's stock, the product is considered available and nothing is returned.
   *
   * @param WC_Product
   *    The product object.
   *
   * @return string
   *    The earliest back in stock date, if any.
   */
  public static function getEarliestBackInStock(\WC_Product $product): string {
    if ($product->get_stock_quantity() > 0) {
      return '';
    }

    $back_in_stock_date = get_post_meta($product->get_id(), self::FIELD_BACK_IN_STOCK_DATE, TRUE);
    if(empty($back_in_stock_date)){
      return '';
    }

    if ($product->is_type('variable')) {
      $variations = $product->get_children();
      foreach ($variations as $variation) {
        $product_variation = wc_get_product($variation);
        $variation_stock_date = get_post_meta($variation, self::FIELD_BACK_IN_STOCK_DATE, TRUE);
        // If a variation has stock or doesn't have a "back in stock" date
        // it's considered to be immediately available for purchase.
        if ($product_variation->get_stock_quantity() > 0 || !$variation_stock_date) {
          return '';
        }
        if ($back_in_stock_date > $variation_stock_date) {
          $back_in_stock_date = $variation_stock_date;
        }
      }
    }

    // Returns the date only if it's in the future.
    return strtotime($back_in_stock_date) > time() ? $back_in_stock_date : '';
  }

  /**
   * Adds back in stock date to delivery time string for simple products and product variants.
   *
   * See https://github.com/netzstrategen/wopa/blob/b3ed454d22f3da7cdee51e7273bda896d04e272c/local-plugins/woocommerce-german-market/inc/WGM_Template.php#L2582
   *
   * @implements woocommerce_de_get_deliverytime_string_label_string
   */
  public static function woocommerce_de_get_deliverytime_string_label_string($label_string, $product) {
    if (!$product) {
      return $label_string;
    }

    if ($back_in_stock_date = self::getEarliestBackInStock($product)) {
      $label_string .= ' <strong>' . WooCommerce::getFormattedBackInStockDateString($back_in_stock_date) . '</strong>';
    }

    return $label_string;
  }

  /**
   * Fixes delivery time is not displayed for variable products.
   *
   * Version 3.10 of WGM introduces changes that prevent delivery time to be
   * displayed for variable products on products listing pages and on single
   * product view page until a variation is selected.
   *
   * @implements wgm_deliverytime_loop
   */
  public static function wgm_deliverytime_loop($output, $label) {
    global $product;

    if (!$label) {
      $output .= \WGM_Template::get_deliverytime_string($product);
    }
    return $output;
  }

  /**
   * Fixes WooCommerce strings translations.
   *
   * @implements gettext
   */
  public static function gettext($translation, $text, $domain) {
    if ($domain === 'woocommerce') {
      if ($text === 'Coupon: %s') {
        $translation = 'Gutschein: %s';
      }
    }
    return $translation;
  }

  /**
   * Changes WGM delivery time label for variable products.
   *
   * @implements woocommerce_de_delivery_time_label_shop
   */
  public static function addsDeliveryTimeLabelSuffix($label, $product = NULL) {
    if (!$product) {
      return $label;
    }

    if ($product->is_type('variable')) {
      $label = __('Delivery Time: from', Plugin::L10N);
    }
    return $label;
  }

  /**
   * Returns the back in stock date string which can be appended to the delivery time.
   *
   * The provided $date_string needs to be in the format used by HTML5 date inputs: 'YYYY-MM-DD'.
   *
   * The given input date must be already validated as in the future
   */
  public static function getFormattedBackInStockDateString($date_string): string {
    // translators: from date, e.g. available 'from 24.10.2019'
    return sprintf(__('from %1$s', Plugin::L10N), date_i18n('d.m.Y', strtotime($date_string)));
  }

  /**
   * Check if product gtin is found for any other product IDs.
   * Checks if product GTIN is found for any other product IDs.
   *
   * @param int $product_id
   *   The Product ID.
   *
   * @param int $gtin
   *   The GTIN.
   *
   * @return string|null
   *   Database query result (post_id of first found duplicate GTIN as string), or null on failure.
   */
  public static function is_existing_gtin($product_id, $gtin) {
    global $wpdb;

    return $wpdb->get_var(
      $wpdb->prepare(
        "
        SELECT pm.post_id
        FROM {$wpdb->postmeta} pm
        WHERE pm.meta_key = %s
        AND pm.meta_value = %s
        AND pm.post_id <> %d
        LIMIT 1
        ",
        self::FIELD_GTIN,
        $gtin,
        $product_id
      )
    );
  }

  /**
   * Wraps is_existing_gtin for responding to ajax requests.
   *
   * @implements wp_ajax_is_existing_gtin
   */
  public static function wp_ajax_is_existing_gtin() {
    $product_id = $_POST['product_id'];
    $gtin = $_POST['gtin'];
    $response = [
      'is_unique' => TRUE,
    ];
    if ($found_duplicate = self::is_existing_gtin($product_id, $gtin)) {
      // For product variations, we want to link to the edit page of the parent product.
      $found_duplicate = get_post_type($found_duplicate) === 'product_variation' ? wp_get_post_parent_id($found_duplicate) : $found_duplicate;
      $duplicate_edit_link = get_edit_post_link($found_duplicate);

      $response = [
        'is_unique' => FALSE,
        'found_duplicate' => $found_duplicate,
        'duplicate_edit_link' => $duplicate_edit_link,
      ];
    }
    wp_send_json($response);
  }

  /**
   * Shows error notice about duplicate GTIN that couldn't be saved.
   *
   * @param string $product_id
   *   The product ID of the product that already contains the given GTIN.
   */
  public static function showDuplicateGtinErrorNotice($product_id = '') {
    // Get link to the edit page of the given product.
    $duplicate_edit_link = '';
    if (!empty($product_id)) {
      $found_duplicate = get_post_type($product_id) === 'product_variation' ? wp_get_post_parent_id($product_id) : $product_id;
      $duplicate_edit_link = get_edit_post_link($found_duplicate);
    }

    \WC_Admin_Meta_Boxes::add_error(sprintf(
      __('The entered GTIN <a href="%s">already exists</a>. It must be changed in order to save the product.', Plugin::L10N),
      $duplicate_edit_link
    ));
  }

  /**
   * Retrieves a list of product variation attributes from current URL.
   *
   * @return array
   *   List of product variation attributes
   */
  public static function getVariationAttributesFromUrl() {
    $attributes = [];
    foreach (array_keys($_GET) as $key) {
      if (
        strpos($key, 'attribute_pa_') === 0 &&
        $attribute = $_GET[$key]
      ) {
        $attributes[$key] = sanitize_text_field($attribute);
      }
    };

    return $attributes;
  }

  /**
   * Finds a product variation that matches a list of attributes.
   *
   * @param int $product_id
   *   The parent product ID.
   * @param array $attributes
   *   List of attributes defining a product variation.
   *
   * @return int
   *   ID of matching product variation.
   */
  public static function getVariationIdByAttributes($product_id, array $attributes) {
    return (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
      new \WC_Product($product_id),
      $attributes
    );
  }

  /**
   * Disables output of related products if over-ride checkbox is enabled.
   */
  public static function disableRelatedProducts(): void {
    global $product;
    $product_id = $product->get_id();
    if (get_post_meta($product->get_id(), self::FIELD_DISABLE_RELATED_PRODUCTS, true) === 'yes') {
      add_filter('woocommerce_product_related_posts_force_display', '__return_false', 40, 2);
      add_filter('woocommerce_related_products', '__return_empty_array');
    }
  }

}
