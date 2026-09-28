<?php

namespace Netzstrategen\ShopStandards;

/**
 * Handles Amazon related functionality.
 */
class Amazon {

  /**
   * Order meta key holding the number shown to customers.
   *
   * @var string
   */
  const CUSTOM_ORDER_NUMBER_META = '_alg_wc_custom_order_number';

  /**
   * The first available shipping method keyed by order ID.
   *
   * @var WC_Shipping_Rate[]
   */
  public static $firstAvailableMethod = [];

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
   * Resolves the custom order number Amazon echoes back into a real order ID.
   *
   * The block checkout never sets `order_awaiting_payment`, so Amazon Pay falls
   * back to the reference above; without this it calls wc_get_order() on an
   * order number and fatals on the false return.
   *
   * @implements woocommerce_amazon_pa_merchant_metadata_reference_id_reverse
   */
  public static function woocommerce_amazon_pa_merchant_metadata_reference_id_reverse($order_id) {
    if (empty($order_id)) {
      return $order_id;
    }
    // Matched on the number first: it is what we send, and a number can collide
    // with an unrelated order's ID.
    $orders = wc_get_orders([
      'limit' => 1,
      'return' => 'ids',
      'meta_key' => static::CUSTOM_ORDER_NUMBER_META,
      'meta_value' => $order_id,
    ]);

    // Orders predating the custom numbering are sent with their plain ID.
    return $orders ? reset($orders) : $order_id;
  }

  /**
   * Returns whether we are using Amazon Pay v2.
   *
   * @return bool
   *   True or false depending on whether Amazon Pay v2 is active.
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
    $available_method = self::get_available_shipping_methods($order);

    if ($available_method) {
      $id = $available_method->get_method_id();
      return [
        'Std DE Dom' => $id,
        'Std DE Dom_2' => $id,
        'Std DE Dom_6' => $id,
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
    $available_method = self::get_available_shipping_methods($order);

    if ($available_method) {
      $label = $available_method->get_label();
      return [
        'Std DE Dom' => $label,
        'Std DE Dom_2' => $label,
        'Std DE Dom_6' => $label,
        'AD DE Dom' => $label,
        'MFN AD Intl' => $label,
      ];
    }
    return $map;
  }

  /**
   * Provides instance id to wp-lister-amazon plugin filter.
   *
   * @implements wpla_shipping_instance_id
   */
  public static function wpla_shipping_instance_id(int $instance_id, $shipping_method_id, $shipping_method_title): int {

    if (isset(self::$firstAvailableMethod)) {
      $shipping_method = reset(self::$firstAvailableMethod);
      return $shipping_method ? $shipping_method->get_instance_id() : $instance_id;
    }
    return $instance_id;
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
  public static function get_available_shipping_methods(?\WC_Order $order) {

    if (!$order) {
      return FALSE;
    }

    if (isset(self::$firstAvailableMethod[$order->get_id()])) {
      return self::$firstAvailableMethod[$order->get_id()];
    }

    $country = $order->get_shipping_country() ?? NULL;
    $postcode = $order->get_shipping_postcode() ?? NULL;
    $state = $order->get_shipping_state() ?? '';
    $city = $order->get_shipping_city() ?? '';

    $order_items = $order->get_items();

    // Country, post code, and items are required to calculate the shipping.
    if (!$country || !$postcode || !$order_items) {
      return FALSE;
    }

    WC()->frontend_includes();

    if (!WC()->session) {
      WC()->session = new \WC_Session_Handler();
      WC()->session->init();
    }

    $customer = new \WC_Customer();
    $cart = new \WC_Cart();
    WC()->cart = $cart;
    $cart_session = new \WC_Cart_Session($cart);
    $shipping = new \WC_Shipping();

    $customer->set_billing_location($country, $state, $postcode, $city);
    $customer->set_shipping_location($country, $state, $postcode, $city);

    // Add all items to cart.
    foreach ($order_items as $order_item) {
      $cart->add_to_cart($order_item->get_product_id(), $order_item->get_quantity());
    }

    // Calculate shipping.
    $packages = $cart->get_shipping_packages();
    $shipping->calculate_shipping($packages);
    $available_methods = $shipping->get_packages();

    if ($available_methods) {
      self::$firstAvailableMethod[$order->get_id()] = reset($available_methods[0]['rates']);
    }
    else {
      self::$firstAvailableMethod[$order->get_id()] = FALSE;
    }

    // Clean up
    $cart->empty_cart();
    $cart_session->destroy_cart_session();
    WC()->session->destroy_session();

    return self::$firstAvailableMethod[$order->get_id()];
  }

}
