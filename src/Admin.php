<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Admin.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Administrative back-end functionality.
 */
class Admin {

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function admin_notices() {
    if (!class_exists('WooCommerce') || !class_exists('Woocommerce_German_Market')) { ?>
      <div class="error below-h3">
        <p><strong><?php _e('Shop Standards plugin requires that WooCommerce and WooCommerce German Market plugins are installed and active.', Plugin::L10N); ?></strong></p>
      </div>
    <?php }
  }

  /**
   * @implements admin_init
   */
  public static function init() {
    // Ensures new product are saved before updating its meta data.
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::saveNewProductBeforeMetaUpdate', 1);

    // Updates product delivery time with the lowest devlivery time between its own variations.
    add_action('updated_post_meta', __NAMESPACE__ . '\WooCommerce::updateDeliveryTime', 10, 3);

    // Updates sale percentage when regular price or sale price are updated.
    add_action('updated_post_meta', __NAMESPACE__ . '\WooCommerce::updateSalePercentage', 10, 4);

    // Adds product custom fields.
    add_action('woocommerce_product_options_general_product_data',  __NAMESPACE__ . '\WooCommerce::woocommerce_product_options_general_product_data');
    add_action('woocommerce_process_product_meta', __NAMESPACE__ . '\WooCommerce::woocommerce_process_product_meta');
    add_action('woocommerce_product_after_variable_attributes', __NAMESPACE__ . '\WooCommerce::woocommerce_product_after_variable_attributes', 10, 3);
    add_action('woocommerce_save_product_variation', __NAMESPACE__ . '\WooCommerce::woocommerce_save_product_variation');
  }

}
