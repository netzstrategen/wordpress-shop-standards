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

}
