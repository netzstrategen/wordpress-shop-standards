<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerceShippingPackages.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Shipping related settings and actions.
 */
class WooCommerceShippingPackages {

  /**
   * Initialize the needed functions and hooks.
   */
  public static function init() {
    // Show multiple shipping methods separately in order emails.
    add_filter('woocommerce_get_order_item_totals', __CLASS__ . '::woocommerce_get_order_item_totals', 10, 2);
  }

  /**
   * Calculates the shipping costs including tax.
   *
   * @param \WC_Order_Item_Shipping $shipping_method
   *   A shipping item object derived from a WC_Order.
   *
   * @return string
   *   The shipping costs including taxes, currency sign, and HTML line-breaks,
   *   for output in the HTML order emails.
   */
  public static function getShippingCostsIncludingTaxes(\WC_Order_Item_Shipping $shipping_method): string {

    if ($shipping_price) {
      $currency = ' ' . get_woocommerce_currency_symbol();
      $shipping_price = floatval($shipping_method->get_total());
      $shipping_price_tax = floatval($shipping_method->get_total_tax());
      $shipping_price_total = $shipping_price + $shipping_price_tax;
      $shipping_price_total_with_symbol = ' - ' . $shipping_price_total . $currency;

      return $shipping_price_total_with_symbol;
    }
    return '';

  }

  /**
   * Separates the shipping methods in new lines in emails.
   *
   * @implements woocommerce_get_order_item_totals
   */
  public static function woocommerce_get_order_item_totals($total_rows, \WC_Order $order): array {
    $shipping_methods = $order->get_shipping_methods();

    if (count($shipping_methods) > 1) {

      $shipping_methods_row = '';
      $count = 1;

      foreach ($shipping_methods as $shipping_method) {
        $shipping_price_total_with_symbol = self::getShippingCostsIncludingTaxes($shipping_method);

        $items = $shipping_method->get_meta('Positionen') ?? '';
        $shipping_methods_row .= "($count) ";
        $shipping_methods_row .= '<strong>' . $shipping_method->get_name() . '</strong>';
        $shipping_methods_row .= $items ? ': ' . $items : '';
        $shipping_methods_row .= $shipping_price_total_with_symbol  . '<br />';
        $count++;
      }

      $total_rows['shipping']['value'] = $shipping_methods_row;
    }
    return $total_rows;
  }

}
