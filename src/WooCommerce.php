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
    // Insufficient variant images button checkbox.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation->ID,
      'label' => __('Variation has insufficient images', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation->ID, TRUE),
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

    // Insufficient variant images button checkbox.
    $insufficient_variant_images = isset($_POST['_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id]) && wc_string_to_bool($_POST['_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_' . Plugin::PREFIX . '_insufficient_variant_images_' . $variation_id, $insufficient_variant_images);
  }

}
