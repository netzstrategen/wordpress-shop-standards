<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerceEnergyLabel.
 */

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce Energy Label related settings and actions.
 */
class WooCommerceEnergyLabel {

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');

    if (is_admin()) {
      return;
    }

    if (get_option(Plugin::PREFIX . '_disable_energy_label') === 'yes') {
      remove_filter('woocommerce_get_price_html', [$GLOBALS['wc_euenergylabel'], 'wc_eu_energy_label_show_rating_after_price']);
    }
  }

  /**
   * Adds WooCommerce Energy Label specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('WooCommerce Energy Label'),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'title' => __('Disable Energy Label for all products.', Plugin::L10N),
      'id' => Plugin::PREFIX . '_disable_energy_label',
      'default'  => 'no',
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

}
