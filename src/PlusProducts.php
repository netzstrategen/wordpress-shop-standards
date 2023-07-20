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
      'options' => WooCommerce::getTaxonomyTermsAsSelectOptions(ProductsPermalinks::TAX_PRODUCT_CAT),
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
    if (WC()->cart->is_empty()) {
      return;
    }
    $plusCategory = get_term_by('id', get_option('_' . Plugin::L10N . '_plus_products_category'), 'product_cat');
    if (is_wp_error($plusCategory) || empty($plusCategory)) {
      return;
    }

    if (static::checkProductsInCartMatchCategoryAndQuantity($plusCategory->slug, 2)) {
      wc_add_notice(__('There is currently only one Plus product in your shopping cart. Please note that you can only complete your order if you add an additional product to your shopping cart.', 'shop-standards'), 'error', [
        'plus-product' => 'invalid',
      ]);
    }
  }

  /**
   * Checks if all products in the cart belong to a specific category and have specific quantity conditions.
   *
   * @param string $category_slug
   * The slug of the category to check against.
   *
   * @param int $product_quantity
   * The minimum quantity of products in the cart.
   *
   * @return bool
   * Returns FALSE if any of the products in the cart does not belong to the given category_slug
   * or the quantity of the products is greater than the minimum quantity.
   */
  public static function checkProductsInCartMatchCategoryAndQuantity(string $category_slug, int $product_quantity): bool {
    $cart_items = WC()->cart->get_cart();
    $cart_contents_count = WC()->cart->get_cart_contents_count();
    foreach ($cart_items as $cart_item) {
      if (!has_term($category_slug, 'product_cat', $cart_item['product_id'])
        || $cart_item['quantity'] >= $product_quantity || $cart_contents_count > 1) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
