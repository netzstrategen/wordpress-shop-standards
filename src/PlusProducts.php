<?php

namespace Netzstrategen\ShopStandards;

/**
 * Plus Products related functionality.
 */
class PlusProducts {

  /**
   * WooCommerce Checkout initialization method.
   */
  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommercePlusProductsSettings');

    if (is_admin()) {
      return;
    }

    add_action('woocommerce_check_cart_items', __CLASS__ . '::displayPlusProductsNotice');
  }

  /**
   * Adds Plus products specific backend settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommercePlusProductsSettings(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Plus Product Settings', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'select',
      'id' => '_' . Plugin::L10N . '_plus_products_category',
      'name' => __('Plus Products Category', Plugin::L10N),
      'options' => WooCommerce::getTaxonomyTermsAsSelectOptions('product_cat'),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Shows an error notice if customer tries to buy only Plus products.
   *
   * @implements woocommerce_check_cart_items
   */
  public static function displayPlusProductsNotice() {
    $plusCategory = get_term_by('id', get_option('_' . Plugin::L10N . '_plus_products_category'), 'product_cat');

    if (is_wp_error($plusCategory) || empty($plusCategory)) {
      return;
    }

    if (static::checkProductsCategoryInCart($plusCategory->slug)) {
      wc_add_notice(__('Currently there are only Plus products in your shopping cart. Please, add at least one product from a different category to complete your order.', Plugin::L10N), 'error');
    }
  }

  /**
   * Checks if cart contains products only from a given category.
   *
   * @param string $categorySlug
   *   The slug of the products category.
   *
   * @return bool
   *   True if the cart contains only products from the given category.
   */
  public static function checkProductsCategoryInCart(string $categorySlug): bool {
    foreach (WC()->cart->get_cart() as $cartItem) {
      if (!has_term($categorySlug, 'product_cat', $cartItem['product_id'])) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
