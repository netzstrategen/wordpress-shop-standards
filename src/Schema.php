<?php

namespace Netzstrategen\ShopStandards;

/**
 * Generic plugin lifetime and maintenance functionality.
 */
class Schema {

  /**
   * Registers activation hook callback.
   */
  public static function activate() {
  }

  /**
   * Registers deactivation hook callback.
   */
  public static function deactivate() {
  }

  /**
   * Registers uninstall hook callback.
   */
  public static function uninstall() {
  }

  /**
   * Retrieves gtin product number and sets gtin property name.
   */
  public static function get_product_gtin($data) {
    global $product;
    $product_id = $product->get_id();
    $gtin = get_post_meta($product_id, '_' . Plugin::PREFIX . '_gtin', TRUE);
    $gtin_prop = "gtin12";
    $gtin_length = strlen($gtin);

    switch ($gtin_length) {
      case $gtin_length == 12:
        $gtin_prop = "gtin12";
        break;

      case $gtin_length == 13:
        $gtin_prop = "gtin13";
        break;

      case $gtin_length == 14:
        $gtin_prop = "gtin14";
        break;

      case $gtin_length == 8:
        $gtin_prop = "gtin8";
        break;
    }

    $data[$gtin_prop] = $gtin;
    if ($data[$gtin_prop] !== '') {
      return $data;
    }
  }

}
