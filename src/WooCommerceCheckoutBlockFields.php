<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerceCheckoutBlockFields.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Adds the classic salutation/title fields to the block checkout.
 *
 * The Checkout block ignores `woocommerce_default_address_fields()`, so
 * WooCommerceSalutation's field never appears there. This registers the block
 * equivalent (plus an academic title) through the Additional Checkout Fields
 * API and mirrors submitted values to the legacy meta keys the classic field
 * already uses, so anything reading it (e.g. exports or emails) needs no
 * change:
 * - salutation: `_billing_salutation` / `_shipping_salutation`
 * - title: `_billing_title` / `_shipping_title`
 */
class WooCommerceCheckoutBlockFields {

  const FIELD_SALUTATION = 'shop-standards/salutation';
  const FIELD_TITLE = 'shop-standards/title';

  /**
   * Maps each field id to its legacy meta name, stored as `_{group}_{name}`.
   *
   * @var string[]
   */
  const META_MAP = [
    self::FIELD_SALUTATION => 'salutation',
    self::FIELD_TITLE => 'title',
  ];

  /**
   * @implements init
   */
  public static function init() {
    // Called directly rather than hooked to `woocommerce_init`: WooCommerce
    // fires that action on `init` priority 0, before Plugin::init() (this
    // method's caller) runs at priority 20.
    static::registerFields();

    // Mirror submitted values to the legacy meta the classic field uses, for
    // any consumer, regardless of whether the fields below are enabled.
    add_action('woocommerce_set_additional_field_value', __CLASS__ . '::mapToLegacyMeta', 10, 4);
    foreach (array_keys(static::META_MAP) as $field_id) {
      add_filter('woocommerce_get_default_value_for_' . $field_id, __CLASS__ . '::prefillFromLegacyMeta', 10, 3);
    }
  }

  /**
   * Registers the additional checkout fields.
   */
  protected static function registerFields() {
    if (!function_exists('woocommerce_register_additional_checkout_field') || get_option(Plugin::PREFIX . '_add_salutation_field') !== 'yes') {
      return;
    }

    woocommerce_register_additional_checkout_field([
      'id' => static::FIELD_SALUTATION,
      'label' => __('Salutation', Plugin::L10N),
      'location' => 'address',
      'type' => 'select',
      'required' => TRUE,
      'options' => WooCommerceSalutation::getSalutationOptions(),
    ]);

    woocommerce_register_additional_checkout_field([
      'id' => static::FIELD_TITLE,
      'label' => __('Academic title', Plugin::L10N),
      'location' => 'address',
      'type' => 'select',
      'required' => FALSE,
      'options' => [
        ['value' => 'Dr.', 'label' => 'Dr.'],
        ['value' => 'Prof.', 'label' => 'Prof.'],
        ['value' => 'Prof. Dr.', 'label' => 'Prof. Dr.'],
      ],
    ]);
  }

  /**
   * Mirrors a submitted field value to the legacy `_{group}_{name}` meta.
   *
   * @param string $key
   *   The additional field id.
   * @param string $value
   *   The submitted value.
   * @param string $group
   *   The field group: 'billing', 'shipping', 'contact' or 'other'.
   * @param \WC_Data $wc_object
   *   The order or customer the value is stored on.
   *
   * @implements woocommerce_set_additional_field_value
   */
  public static function mapToLegacyMeta($key, $value, $group, $wc_object) {
    if (!isset(static::META_MAP[$key]) || !in_array($group, ['billing', 'shipping'], TRUE)) {
      return;
    }
    $wc_object->update_meta_data('_' . $group . '_' . static::META_MAP[$key], $value);
  }

  /**
   * Prefills a field from its legacy meta value, if present.
   *
   * @param mixed $value
   *   The current default value.
   * @param string $group
   *   The field group.
   * @param \WC_Data $wc_object
   *   The order or customer to read from.
   *
   * @return mixed
   *   The legacy value if set, otherwise the original default.
   *
   * @implements woocommerce_get_default_value_for_{field_id}
   */
  public static function prefillFromLegacyMeta($value, $group, $wc_object) {
    if (!in_array($group, ['billing', 'shipping'], TRUE) || !is_object($wc_object)) {
      return $value;
    }
    $field_id = str_replace('woocommerce_get_default_value_for_', '', current_filter());
    if (!isset(static::META_MAP[$field_id])) {
      return $value;
    }
    $legacy = $wc_object->get_meta('_' . $group . '_' . static::META_MAP[$field_id]);
    return ($legacy !== '' && $legacy !== NULL) ? $legacy : $value;
  }

}
