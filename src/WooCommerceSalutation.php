<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerceSalutation.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Checkout related settings and actions.
 */
class WooCommerceSalutation {

  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');

    if (is_admin()) {
      return;
    }

    // Add salutation field to checkout.
    if (get_option(Plugin::PREFIX . '_add_salutation_field') === 'yes') {
      add_filter('woocommerce_default_address_fields', __CLASS__ . '::woocommerce_default_address_fields');
    }
  }

  /**
   * Adds checkout specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Salutation', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'title' => __('Salutation field', Plugin::L10N),
      'desc' => __('Add salutation field in checkout', Plugin::L10N),
      'id' => Plugin::PREFIX . '_add_salutation_field',
      'default'  => 'no',
      'desc_tip' => __('If checked a salutation field will be added to the shipping and billing address fields.', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Add salutation field for billing and shipping.
   *
   * @implements woocommerce_default_address_fields
   */
  public static function woocommerce_default_address_fields(?array $fields): array {
    $fields['salutation'] = [
      'label' => __('Salutation', Plugin::L10N),
      'class' => ['form-row-wide'],
      'type' => 'select',
      'options' => [
        '' => '',
        'Mrs' => __('Mrs', Plugin::L10N),
        'Mr' => __('Mr', Plugin::L10N),
        'Company' => __('Company', Plugin::L10N),
      ],
      'priority' => 5,
    ];
    return $fields;
  }


}
