<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerceBlocks.
 */

namespace Netzstrategen\ShopStandards;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Loads frontend assets for the WooCommerce Cart and Checkout blocks.
 *
 * The Cart and Checkout blocks render line-item metadata and checkout steps
 * client-side from the Store API, so PHP templates and most classic hooks do
 * not apply there. This integration loads two small, independent scripts:
 * - block-attributes: groups the product-attribute rows (tagged by
 *   WooCommerce::woocommerce_get_item_data() with the
 *   `shop-standards-attribute--additional` class) and the variation
 *   attributes behind a collapsible toggle, matching the classic cart
 *   template's behaviour.
 * - block-labels: adjusts block-only strings — via the checkout filter
 *   registry where a filter exists (e.g. removing German Market's
 *   "Veranschlagte" prefix from the order total), otherwise via direct DOM
 *   text updates (e.g. the sale badge's "Sparen Sie" -> "Sie sparen", per
 *   Asana ID-835, which no filter exposes).
 * - block-vat: shows the EU VAT Number plugin's VAT field only for companies
 *   outside Germany (reads the salutation field registered by
 *   WooCommerceCheckoutBlockFields).
 *
 * Registered on both `woocommerce_blocks_cart_block_registration` and
 * `woocommerce_blocks_checkout_block_registration` in plugin.php — both fire
 * before `init`, so registration cannot wait for Plugin::init().
 */
class WooCommerceBlocks implements IntegrationInterface {

  /**
   * {@inheritdoc}
   */
  public function get_name() {
    return Plugin::PREFIX . '-blocks';
  }

  /**
   * {@inheritdoc}
   */
  public function initialize() {
    $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
    $version = Plugin::getGitVersion();
    $base_url = Plugin::getBaseUrl();

    wp_register_script(Plugin::PREFIX . '-block-attributes', $base_url . '/dist/scripts/block-attributes' . $suffix . '.js', ['wp-data', 'wc-settings'], $version, TRUE);
    wp_register_script(Plugin::PREFIX . '-block-labels', $base_url . '/dist/scripts/block-labels' . $suffix . '.js', ['wp-data', 'wc-blocks-checkout'], $version, TRUE);
    if (class_exists('WC_EU_VAT_Number')) {
      wp_register_script(Plugin::PREFIX . '-block-vat', $base_url . '/dist/scripts/block-vat' . $suffix . '.js', ['wp-data'], $version, TRUE);
    }

    wp_register_style($this->get_name(), $base_url . '/dist/styles/blocks' . $suffix . '.css', [], $version);
    wp_enqueue_style($this->get_name());
  }

  /**
   * {@inheritdoc}
   */
  public function get_script_handles() {
    $handles = [Plugin::PREFIX . '-block-attributes', Plugin::PREFIX . '-block-labels'];
    if (class_exists('WC_EU_VAT_Number')) {
      $handles[] = Plugin::PREFIX . '-block-vat';
    }
    return $handles;
  }

  /**
   * {@inheritdoc}
   */
  public function get_editor_script_handles() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function get_script_data() {
    return [
      'productAttributesLabel' => __('Product properties', Plugin::L10N),
    ];
  }

}
