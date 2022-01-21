<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\ProductDefects.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Used or defective goods user consent checkbox functionality.
 */
class ProductDefects {

  const FIELD_TITLE_TEXT = Plugin::PREFIX . '_product__defects_title_text';
  const FIELD_DESCRIPTION_TEXT = Plugin::PREFIX . '_product_defects_description_text';
  const FIELD_ATTRIBUTE_TEXT = Plugin::PREFIX . '_product_defects_attr_text';
  const FIELD_PRODUCT_ATTRIBUTE_NAME = Plugin::L10N . '_product_defects_attribute_name';
  const FIELD_PRODUCT_ATTRIBUTE_VALUE = Plugin::L10N . '_product_defects_attribute_value';

  /**
   * Checkbox consent initialization method.
   */
  public static function init() {
    add_action('wp', __CLASS__ . '::display_product_checkbox');
    add_filter('woocommerce_available_variation', __CLASS__ . '::pass_variation_attribute', 10, 3);
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');
  }

  /**
   * Determines if defective or used checkbox consent should be shown based on settings.
   *
   * @implemements wp
   */
  public static function display_product_checkbox() {
    if (!is_product()) {
      return;
    }
    if (self::get_display_product_attribute(wc_get_product())) {
      add_action('woocommerce_before_add_to_cart_button', __CLASS__ . '::woocommerce_before_add_to_cart_button');
      // Logs consent to defective or used product when product is added to cart.
      add_action('woocommerce_add_to_cart', __CLASS__ . '::log_consent_file');
    }
  }

  /**
   * Returns the product attribute value to be displayed based on product and settings.
   */
  private static function get_display_product_attribute(\WC_Product $product): ?string {
    $tax_name = get_option(self::FIELD_PRODUCT_ATTRIBUTE_NAME);
    $tax_value = get_option(self::FIELD_PRODUCT_ATTRIBUTE_VALUE);
    if (empty($tax_name) || empty($tax_value)) {
      return NULL;
    }

    $attr_slug = 'pa_' . $tax_name;
    $attr_value = $product->get_attribute($attr_slug);
    if (empty($attr_value)) {
      return NULL;
    }
    // Returns value when is not found in the product attrs as expected.
    // But also when is a variable product which will be handled via JS.
    return $product->is_type('variable') || strpos($attr_value, $tax_value) === FALSE ? $attr_value : NULL;
  }

  /**
   * Logs consent to defective or used product.
   *
   * @implemements woocommerce_add_to_cart
   */
  public static function log_consent_file() {
    if (!isset($_POST['used-goods-consent'])) {
      return;
    }

    $uploads_dir = wp_upload_dir()['basedir'] . '/' . Plugin::PREFIX;
    $log_file = $uploads_dir . '/defects-consent.log';
    if (!dir($uploads_dir)) {
      wp_mkdir_p($uploads_dir);
    }
    $data = [
      'timestamp' => date_i18n('c'),
      'user' => get_current_user_id(),
      'referrer' => $_SERVER['HTTP_REFERER'],
      'consent' => $_POST['used-goods-consent'],
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
    $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($log_file, $data . "\n", FILE_APPEND);
  }

  /**
   * Displays the checkbox and agreement text.
   *
   * @implemements woocommerce_before_add_to_cart_button
   */
  public static function woocommerce_before_add_to_cart_button() {
    $product_attribute = self::get_display_product_attribute(wc_get_product());
    echo '<div class="product-defects__checkbox-container">
    <b>' . get_option(self::FIELD_TITLE_TEXT) . '</b>
    <div class="product-defects__checkbox-detail">
     <p>' . get_option(self::FIELD_DESCRIPTION_TEXT) . '</p>
     <p>
     <input required data-checkbox-toggle class="checkbox-box" type="checkbox" id="used-goods-consent" name="used-goods-consent" value="used-goods-consent-true"/>
     <span>' . get_option(self::FIELD_ATTRIBUTE_TEXT) . '</span>
     </p>
     <span class="product-defects__attribute">';
    echo $product_attribute;
    echo '</span>
    </div>
    </div>';
  }

  /**
   * Passes the variation attribute to jquery object.
   *
   * @implemements woocommerce_available_variation
   */
  public static function pass_variation_attribute($data, $product, $variation) {
    $data['used_goods_consent_attribute'] = self::get_display_product_attribute($variation);
    return $data;
  }

  /**
   * Adds checkout specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $module_settings = [
      [
        'type' => 'title',
        'name' => __('Product Defects Settings', Plugin::L10N),
      ],
      [
        'type' => 'select',
        'id' => self::FIELD_PRODUCT_ATTRIBUTE_NAME,
        'name' => __('Product attribute to verify', Plugin::L10N),
        'options' => ['' => __('None', Plugin::L10N)] + WooCommerce::getAvailableAttributes(),
      ],
      [
        'type' => 'text',
        'title' => __('Target value for attribute', Plugin::L10N),
        'id' => self::FIELD_PRODUCT_ATTRIBUTE_VALUE,
        'desc' => __('Enter the only value on which the checkbox must not be shown', Plugin::L10N)
      ],
      [
        'type' => 'text',
        'title' => __('Title text', Plugin::L10N),
        'id' => self::FIELD_TITLE_TEXT,
      ],
      [
        'type' => 'textarea',
        'title' => __('Description text', Plugin::L10N),
        'id' => self::FIELD_DESCRIPTION_TEXT,
      ],
      [
        'type' => 'text',
        'title' => __('Checkbox label text', Plugin::L10N),
        'id' => self::FIELD_ATTRIBUTE_TEXT,
      ],
      [
        'type' => 'sectionend',
        'id' => Plugin::L10N . '_product_defects_section',
      ]
    ];

    return array_merge($settings, $module_settings);
  }

}
