<?php

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce related functionality.
 */
class WooCommerce {

  /**
   * Adds strike price for variable products above regular product price labels.
   *
   * @see https://github.com/woocommerce/woocommerce/issues/16169
   *
   * @implements woocommerce_variable_sale_price_html
   * @implements woocommerce_variable_price_html
   */
  public static function woocommerce_variable_sale_price_html($price, $product) {
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
   * Enables revisions for product descriptions.
   *
   * @implements woocommerce_register_post_type_product
   */
  public static function woocommerce_register_post_type_product($args) {
    $args['supports'][] = 'revisions';
    return $args;
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
   * Adds backordering with proper status messages.
   *
   * Backoredering is added and status messages displayed for every product
   * whether stock managing is enabled or it's available.
   *
   * @implements woocommerce_get_availability
   */
  public static function woocommerce_get_availability($stock, $product) {
    $product->set_manage_stock('yes');
    $product->set_backorders('yes');
    if ($product->managing_stock() && $product->backorders_allowed()) {
      if (!$product->is_in_stock()) {
        $stock['availability'] = __('Out of stock', 'woocommerce');
        $stock['class'] = 'out-of-stock';
      }
      else {
        $stock['availability'] = __('In stock', 'woocommerce');
        $stock['class'] = 'in-stock';
      }
    }
    return $stock;
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
    $hide_add_to_cart = get_post_meta($product_id, '_' . Plugin::PREFIX . '_hide_add_to_cart_button', TRUE);
    return !wc_string_to_bool($hide_add_to_cart);
  }

  /**
   * Displays custom fields for single products.
   *
   * @implements woocommerce_product_options_general_product_data
   */
  public static function woocommerce_product_options_general_product_data() {
    // GTIN field.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_gtin',
      'label' => __('GTIN', Plugin::L10N),
      'desc_tip' => 'true',
      'description' => __('Enter the Global Trade Item Number', Plugin::L10N),
    ]);
    echo '</div>';
    // ERP/Inventory ID field.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_erp_inventory_id',
      'label' => __('ERP/Inventory ID', Plugin::L10N),
    ]);
    echo '</div>';
    // Hide add to cart button.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_checkbox([
      'id' => '_' . Plugin::PREFIX . '_hide_add_to_cart_button',
      'label' => __('Hide add to cart button', Plugin::L10N),
    ]);
    echo '</div>';
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
      '_' . Plugin::PREFIX . '_gtin',
      '_' . Plugin::PREFIX . '_erp_inventory_id',
      '_' . Plugin::PREFIX . '_product_notes',
    ];

    foreach ($custom_fields as $field) {
      if (isset($_POST[$field])) {
        if ($field) {
          update_post_meta($post_id, $field, $_POST[$field]);
        }
        else {
          delete_post_meta($post_id, $field);
        }
      }
    }

    // Hide add to cart button.
    $hide_add_to_cart_index = '_' . Plugin::PREFIX . '_hide_add_to_cart_button';
    $hide_add_to_cart_value = isset($_POST[$hide_add_to_cart_index]) && wc_string_to_bool($_POST[$hide_add_to_cart_index]) ? 'yes' : 'no';
    update_post_meta($post_id, $hide_add_to_cart_index, $hide_add_to_cart_value);
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

  /**
   * Hides 'add to cart' button for products from specific categories or brands.
   *
   * @implements wp
   */
  public static function wp() {
    if (empty($product = wc_get_product())) {
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
    if (empty(get_field('acf_hide_add_to_cart_product_notice', 'option')) || empty($product = wc_get_product())) {
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
    if ($excluded_terms = get_field('acf_hide_add_to_cart_' . $taxonomy_name, 'option')) {
      return has_term($excluded_terms, $taxonomy_name, $post_id);
    }
  }

  /**
   * Creates custom fields for product variations.
   *
   * @implements woocommerce_product_after_variable_attributes
   */
  public static function woocommerce_product_after_variable_attributes($loop, $variation_id, $variation) {
    // Variation GTIN field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_gtin[' . $loop . ']',
      'label' => __('GTIN:', Plugin::L10N),
      'placeholder' => __('Enter the Global Trade Item Number', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_' . Plugin::PREFIX . '_gtin', TRUE),
    ]);
    echo '</div>';
    // Variation ERP/Inventory ID field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_' . Plugin::PREFIX . '_erp_inventory_id[' . $loop . ']',
      'label' => __('ERP/Inventory ID:', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_' . Plugin::PREFIX . '_erp_inventory_id', TRUE),
    ]);
    echo '</div>';
    // Variation hide add to cart button.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_' . Plugin::PREFIX . '_hide_add_to_cart_button_' . $variation->ID,
      'label' => __('Hide add to cart button', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_' . Plugin::PREFIX . '_hide_add_to_cart_button', TRUE),
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

    // Variation GTIN and ERP/Inventory ID fields.
    $custom_fields = [
      '_' . Plugin::PREFIX . '_gtin',
      '_' . Plugin::PREFIX . '_erp_inventory_id',
    ];
    foreach ($custom_fields as $field) {
      if (isset($_POST[$field]) && isset($_POST[$field][$loop])) {
        if ($_POST[$field][$loop]) {
          update_post_meta($variation_id, $field, $_POST[$field][$loop]);
        }
        else {
          delete_post_meta($post_id, $field);
        }
      }
    }

    // Hide add to cart button.
    $hide_add_to_cart_button = isset($_POST['_' . Plugin::PREFIX . '_hide_add_to_cart_button_' . $variation_id]) && wc_string_to_bool($_POST['_' . Plugin::PREFIX . '_hide_add_to_cart_button_' . $variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_' . Plugin::PREFIX . '_hide_add_to_cart_button', $hide_add_to_cart_button);

    // Insufficient images checkbox.
    $insufficient_variant_images = isset($_POST['_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id]) && wc_string_to_bool($_POST['_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_' . Plugin::PREFIX . '_insufficient_variant_images', $insufficient_variant_images);
  }

}
