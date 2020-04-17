<?php

namespace Netzstrategen\ShopStandards;

use WGM_Template;

/**
 * WooCommerce German Market template class overrides.
 */
class WGMTemplate extends WGM_Template {

  /**
   * Overrides method add_template_loop_shop from class WGM_Template.
   *
   * Version 3.10 of WGM introduces changes that prevent delivery time to be
   * displayed for variable products on products listing pages and on single
   * product view page until a variation is selected.
   *
   * See https://github.com/netzstrategen/wopa/blob/4dab5a48216d0fa3e42f85d1107e018fc89a3196/local-plugins/woocommerce-german-market/inc/WGM_Template.php#L2519-L2523
   *
   * @implements add_template_loop_shop
   */
  public static function add_template_loop_shop($product = NULL) {
    $label_string = self::get_deliverytime_string($product);
    $lieferzeit_output = '';

    $delivery_time_label = __('Delivery Time:', 'woocommerce-german-market');
    $delivery_time_label = apply_filters('woocommerce_de_delivery_time_label_shop', $delivery_time_label);

    // If the product is a product variation, check if each variation has the
    // same delivery time => if not, do not display delivery time! Add
    // "add_filter('woocommerce_de_use_delivery_time_of_variable_product',
    // '__return_true');" to your functions.php to use the delivery time of the
    // variable product (parent product)
    if (is_a($product, 'WC_Product_Variable')) {
      if (!apply_filters('woocommerce_de_use_delivery_time_of_variable_product', FALSE)) {

        if (apply_filters('woocommerce_de_avoid_check_same_delivery_time_show_parent', FALSE)) {
          $label_string = '';
        }
        else {
          $label_string = self::get_variable_data_quick($product, 'delivery_time');
        }

        if (!empty($label_string)) {
          $lieferzeit_output = apply_filters('wgm_deliverytime_loop', $delivery_time_label . ' ' . $label_string, $label_string);
        }
        else {
          $lieferzeit_output == '';
        }
      }
      else {
        // Use delivery time of partent product (the variable product).
        $lieferzeit_output = apply_filters('wgm_deliverytime_loop', $delivery_time_label . ' ' . $label_string, $label_string);
      }
    }
    else {
      $lieferzeit_output = apply_filters('wgm_deliverytime_loop', $delivery_time_label . ' ' . $label_string, $label_string);
    }

    // If product is out of stock, don't show delivery time.
    if (!self::show_delivery_time_if_product_is_not_in_stock($product)) {
      $lieferzeit_output = apply_filters('gm_delivery_time_message_if_out_of_stock', '', $product);
    }

    // Output delivery time.
    if (!empty($lieferzeit_output)) {
      $lieferzeit_and_markup = '<div class="wgm-info shipping_de shipping_de_string ' . 'delivery-time-' . sanitize_title($label_string) . '">
        <small>
          <span>' . $lieferzeit_output . '</span>
        </small>
      </div>';

      echo apply_filters('gm_lieferzeit_output_lieferzeit_and_markup', $lieferzeit_and_markup, $lieferzeit_output);
    }
  }

}
