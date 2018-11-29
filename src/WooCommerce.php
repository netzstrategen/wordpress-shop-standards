<?php

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce related functionality.
 */
class WooCommerce {

  /**
   * Enables revisions for product descriptions.
   *
   * @implements woocommerce_register_post_type_product
   */
  public static function woocommerce_register_post_type_product($args) {
    $args['supports'][] = 'revisions';
    return $args;
  }

}
