<?php

namespace Netzstrategen\ShopStandards;

/**
 * Module meant to handle custom product fields.
 */
class ProductFieldsManager
{
  const FIELD_PRODUCT_FIELDS_LIST = Plugin::PREFIX . '_custom_product_fields';

  /**
   * Init module.
   */
  public static function init(): void {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');
  }

  /**
   * @implemements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $field_list = apply_filters(Plugin::PREFIX . '/display_custom_product_fields', []);
    if (empty($field_list)) {
      return $settings;
    }

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
      ],
      [
        'type' => 'sectionend',
        'id' => Plugin::L10N . '_custom_product_fields_section',
      ],
    ]);
  }

  /**
   * Determines if the given field must be displayed according to settings.
   */
  public static function show_field(string $field_name): bool {
    $display_fields = get_option(self::FIELD_PRODUCT_FIELDS_LIST);
    if ($display_fields === FALSE) {
      // Default state when no fields have been selected yet, all are shown.
      return TRUE;
    }
    return in_array($field_name, $display_fields);
  }
}
