<?php

namespace Netzstrategen\ShopStandards;

class ProductFieldsManager
{
  const FIELD_PRODUCT_FIELDS_LIST = Plugin::PREFIX . '_custom_product_fields';

  public static function init(): void {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');
  }

  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $field_list = apply_filters(Plugin::PREFIX . '/display_custom_product_fields', []);
    return array_merge($settings, [
      [
        'type' => 'title',
        'name' => __('Product custom fields manager', Plugin::L10N),
      ],
      [
        'type' => 'multiselect',
        'id' => self::FIELD_PRODUCT_FIELDS_LIST,
        'name' => __('Custom product fields to be displayed', Plugin::L10N),
        'options' => $field_list,
        'css' => 'height:auto',
      ]
    ]);
  }
}
