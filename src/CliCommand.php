<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\CliCommand.
 */

namespace Netzstrategen\ShopStandards;

use WP_CLI;

/**
 * WP-CLI shop-standards commands implementation.
 */
class CliCommand extends \WP_CLI_Command {

  /**
   * Updates products delivery time.
   *
   * ## OPTIONS
   *
   * [<product-ids-list>]
   * : Updates delivery time of a set of products, given their IDs as a comma separated list.
   *
   * [--all]
   * : Updates all products.
   * ---
   * default: ''
   * ---
   *
   * ## EXAMPLES
   *
   *     wp shop-standards refreshDeliveryTime 2165, 2166, 2167
   *     wp shop-standards refreshDeliveryTime --all
   */
  public function refreshDeliveryTime(array $args, array $options) {
    if ($product_ids = static::getProductsToUpdate($args, $options)) {
      static::updateProductsMeta($product_ids, 'delivery_time');
      WP_CLI::success('Delivery times have been sucessfully updated.');
    }
    else {
      WP_CLI::error('A comma separated list of products IDs is needed. Use option --all to update all products. See `wp shop-standards refreshDeliveryTime --help`.');
    }
  }

  /**
   * Updates a given meta field for a list of products.
   *
   * @param array $product_ids
   *   List of IDs of products to update.
   * @param string $meta_key
   *   Meta field to update.
   */
  public static function updateProductsMeta($product_ids, $meta_key) {
    foreach ($product_ids as $product_id) {
      try {
        Admin::updateProductDeliveryTime(FALSE, $product_id, '_lieferzeit');
      }
      catch (\Exception $e) {
        WP_CLI::error($e->getMessage());
        exit();
      }
    }
  }

  /**
   * Builds a list of product IDs from WP-CLI command arguments.
   */
  public static function getProductsToUpdate(array $args, array $options) {
    $options = wp_parse_args($options, ['all' => '']);
    if ($options['all']) {
      $product_ids = wc_get_products([
        'return' => 'ids',
        'limit' => -1,
      ]);
    }
    elseif (isset($args[0])) {
      $input_product_ids = explode(',', trim($args[0], ','));
      $product_ids = [];
      // Collect each valid user entered product ID, skip invalid ones.
      foreach ($input_product_ids as $product_id) {
        if (is_numeric($product_id) && (int) $product_id == $product_id) {
          $product_ids[] = (int) $product_id;
        }
      }
    }
    else {
      $product_ids = [];
    }
    return $product_ids;
  }

}
