<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Settings.
 */

namespace Netzstrategen\ShopStandards;

use WC_Admin_Settings;

/**
 * Configuration for the plugin settings sections.
 */
class Settings {

  public static function init() {
    add_action('woocommerce_settings_tabs_array', __CLASS__ . '::woocommerce_settings_tabs_array', 30);
    add_action('woocommerce_settings_shop_standards', __CLASS__ . '::woocommerce_settings_shop_standards');
    add_action('woocommerce_settings_save_shop_standards', __CLASS__ . '::woocommerce_settings_save_shop_standards');
  }

  /**
   * Defines plugin configuration settings.
   *
   * @return array
   */
  public static function getSettings(): array {
    return apply_filters('woocommerce_get_settings_shop_standards', []);
  }

  /**
   * Adds a Shop Standards section tab.
   *
   * @implements woocommerce_settings_tabs_array
   */
  public static function woocommerce_settings_tabs_array(array $tabs): array {
    $tabs['shop_standards'] = __('Shop Standards', Plugin::L10N);
    return $tabs;
  }

  /**
   * Adds settings fields to corresponding WooCommerce settings section.
   *
   * @implements woocommerce_settings_<current_tab>
   */
  public static function woocommerce_settings_shop_standards() {
    $settings = static::getSettings();
    WC_Admin_Settings::output_fields($settings);
  }

  /**
   * Triggers setting save.
   *
   * @implements woocommerce_settings_save_<current_tab>
   */
  public static function woocommerce_settings_save_shop_standards() {
    $settings = static::getSettings();
    WC_Admin_Settings::save_fields($settings);
  }

}
