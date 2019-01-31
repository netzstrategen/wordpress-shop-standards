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
   * Updates products sale discounted percentage.
   */
  public function refreshSalePercentage(array $args, array $options) {
    $options = wp_parse_args($options, ['all' => '']);
    if ($options['all']) {
      $product_ids = wc_get_products([
        'return' => 'ids',
        'limit' => -1,
      ]);
      static::updateSalePercentage($product_ids);
      WP_CLI::success('Sale percentage have been sucessfully updated.');
    }
    elseif (isset($args[0])) {
      $product_ids = explode(',', trim($args[0], ','));
      static::updateSalePercentage($product_ids);
    }
    else {
      WP_CLI::error('A comma separated list of products IDs is needed. Use option --all to update all products.');
    }
  }

  /**
   * Forces the update of products sale discounted percentage.
   *
   * @param array $product_ids
   *   List of product unique identifiers.
   */
  public static function updateSalePercentage($product_ids) {
    foreach ($product_ids as $product_id) {
      if (!$product_id = is_numeric($product_id) && (int) $product_id == $product_id ? (int) $product_id : 0) {
        WP_CLI::error('Invalid product ID.');
        exit();
      }
      try {
        Admin::updateSalePercentage(FALSE, $product_id, '_sale_price', FALSE);
      }
      catch (\Exception $e) {
        WP_CLI::error($e->getMessage());
        exit();
      }
    }
  }

}
