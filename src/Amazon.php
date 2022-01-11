<?php

namespace Netzstrategen\ShopStandards;

/**
 * Handles Amazon related functionality.
 */
class Amazon {

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
   */
  public static function isAmazonPayV2Checkout(): bool {
    $is_amazon_pay_active = is_plugin_active('woocommerce-gateway-amazon-payments-advanced/woocommerce-gateway-amazon-payments-advanced.php');
    if ($is_amazon_pay_active) {
      return defined('WC_AMAZON_PAY_VERSION') && version_compare(WC_AMAZON_PAY_VERSION, '2.0', '>=');
    }
    return false;
  }

}
