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
   * Retrieves GTIN product number and sets GTIN property name.
   *
   * @implements woocommerce_structured_data_product
   */
  public static function get_product_gtin($data) {
    global $product;

    if (!$gtin = get_post_meta($product->get_id(), '_' . Plugin::PREFIX . '_gtin', TRUE)) {
      return $data;
    }

    switch (strlen(trim($gtin))) {
      case 8:
        $gtin_format_type = 'gtin8';
        break;

      case 13:
        $gtin_format_type = 'gtin13';
        break;

      case 14:
        $gtin_format_type = 'gtin14';
        break;

      default:
        $gtin_format_type = 'gtin12';
    }

    $data[$gtin_format_type] = $gtin;
    return $data;
  }

}
