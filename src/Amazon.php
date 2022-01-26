<?php

namespace Netzstrategen\ShopStandards;

/**
 * Handles Amazon related functionality.
 */
class Amazon {

  /**
   * First Available Shipping Method or null.
   *
   * @var WC_Shipping_Rate|null
   */
  public static $firstAvailableMethod = NULL;

  /**
   * Replaces the order ID of imported Amazon orders with the custom order ID.
   *
   * @implements woocommerce_amazon_pa_update_checkout_session_payload
   */
  public static function woocommerce_amazon_pa_update_checkout_session_payload($payload, $checkout_session_id, $order) {
    if (empty($payload) || empty($order) || !self::isAmazonPayV2Checkout()) {
      return $payload;
    }
    $payload['merchantMetadata']['merchantReferenceId'] = $order->get_order_number();

    return $payload;
  }

  /**
   * Returns whether we are using Amazon Pay v2.
   *
   * @return bool
   *   True or false depending is Amazon Pay v2 is active.
   */
  public static function isAmazonPayV2Checkout(): bool {
    $is_amazon_pay_active = is_plugin_active('woocommerce-gateway-amazon-payments-advanced/woocommerce-gateway-amazon-payments-advanced.php');
    if ($is_amazon_pay_active) {
      return defined('WC_AMAZON_PAY_VERSION') && version_compare(WC_AMAZON_PAY_VERSION, '2.0', '>=');
    }
    return FALSE;
  }

  /**
   * Provides shipping method ID to wp-lister-amazon plugin filter.
   *
   * @implements wpla_shipping_service_id_map
   */
  public static function wpla_shipping_service_id_map($map, $order) {
    $available_method = self::$firstAvailableMethod ?? self::get_available_shipping_methods($order);

    if ($available_method) {
      $id = $available_method->get_method_id();
      return [
        'Std DE Dom' => $id,
        'Std DE Dom_2' => $id,
        'AD DE Dom' => $id,
        'MFN AD Intl' => $id,
      ];
    }
    return $map;
  }

  /**
   * Provides shipping method title to wp-lister-amazon plugin filter.
   *
   * @implements wpla_shipping_service_title_map
   */
  public static function wpla_shipping_service_title_map($map, $order) {
    $available_method = self::$firstAvailableMethod ?? self::get_available_shipping_methods($order);

    if ($available_method) {
      $label = $available_method->get_label();
      return [
        'Std DE Dom' => $label,
        'Std DE Dom_2' => $label,
        'AD DE Dom' => $label,
        'MFN AD Intl' => $label,
      ];
    }
    return $map;
  }

  /**
   * Recreates WC_Cart instance and returns the first available shipping method.
   *
   * @param WC_Order $order
   *   Woocommerce order to calculate its shipping method.
   *
   * @return WC_Shipping_Rate|bool
   *   The first available shipping method or FALSE.
   */
  public static function get_available_shipping_methods(\WC_Order $order) {
    $country = $order->get_shipping_country() ?? NULL;
    $postcode = $order->get_shipping_postcode() ?? NULL;
    $state = $order->get_shipping_state() ?? '';
    $city = $order->get_shipping_city() ?? '';

    $order_items = $order->get_items();

    // Country, post code, and items are required to calculate the shipping.
    if (!$country || !$postcode || empty($order_items)) {
      return FALSE;
    }

    $customer = new \WC_Customer();
    $cart = new \WC_Cart();
    $shipping = new \WC_Shipping();

    // Reset shipping first.
    $shipping->reset_shipping();
    $customer->set_billing_location($country, $state, $postcode, $city);
    $customer->set_shipping_location($country, $state, $postcode, $city);

    // Empty cart.
    $cart->empty_cart();

    // Add all items to cart.
    foreach ($order_items as $order_item) {
      $cart->add_to_cart($order_item->get_product_id(), $order_item->get_quantity());
    }

    // Calculate shipping.
    $packages = $cart->get_shipping_packages();
    $shipping->calculate_shipping($packages);
    $available_methods = $shipping->get_packages();

    if (!empty($available_methods)) {
      self::$firstAvailableMethod = current($available_methods[0]['rates']);
    }

    return self::$firstAvailableMethod;
  }

}
