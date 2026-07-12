<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\TrustedShopsCartBanner.
 */

namespace Netzstrategen\ShopStandards;

use Netzstrategen\WooCommerceReputations\Settings as ReputationsSettings;

/**
 * Renders the eTrusted (Trusted Shops) cart banner in the Cart block.
 *
 * The classic cart gets this banner from woocommerce-reputations, hooked to a
 * theme-only template hook the Cart block has no equivalent of. Appends the
 * same widget tag after the block's Proceed to Checkout block, following
 * ShopAdvantages, at a later priority so it renders after the advantages it
 * already appends there.
 */
class TrustedShopsCartBanner {

  /**
   * @implements init
   */
  public static function init(): void {
    if (!class_exists(ReputationsSettings::class)) {
      return;
    }
    add_filter('render_block_woocommerce/proceed-to-checkout-block', __CLASS__ . '::appendToBlockProceedToCheckout', 20);
  }

  /**
   * Appends the eTrusted widget tag after the Cart block's checkout button.
   *
   * @param string $block_content
   *   The block's own rendered HTML, already including anything ShopAdvantages
   *   appended.
   *
   * @return string
   *   The block's HTML with the eTrusted widget tag appended.
   *
   * @implements render_block_woocommerce/proceed-to-checkout-block
   */
  public static function appendToBlockProceedToCheckout(string $block_content): string {
    $widget_id = ReputationsSettings::getOption('cart_banner/widget_id');
    if (!$widget_id) {
      return $block_content;
    }
    return $block_content . '<etrusted-widget data-etrusted-widget-id="' . esc_attr($widget_id) . '"></etrusted-widget>';
  }

}
