<?php

namespace Netzstrategen\ShopStandards;
/**
 * Handles Amazon related functionality.
 */
class Amazon {

  /**
   * Returns Payload with modified custom order id for Amazon.
   * 
   * @implements woocommerce_amazon_pa_update_checkout_session_payload.
   */
  public static function woocommerce_amazon_pa_update_checkout_session_payload ($payload, $checkout_session_id, $order) {
    
    if (empty($payload) && empty($order) && !self::isAmazonPayV2Checkout()) {
      return $payload;
    }

    $payload['merchantMetadata']['merchantReferenceId'] = $order->get_order_number();

    return $payload;
  }

  /**
   * Check if we are using Amazon V2.
   *
   * @return bool
   */
  public static function isAmazonPayV2Checkout(): bool {
    $is_amazon_pay_active = is_plugin_active('woocommerce-gateway-amazon-payments-advanced/woocommerce-gateway-amazon-payments-advanced.php');
    if ($is_amazon_pay_active && isset(WC()->session)) {
      return defined('WC_AMAZON_PAY_VERSION') && version_compare(WC_AMAZON_PAY_VERSION, '2.0', '>=');
    }
    return false;
  }

}
