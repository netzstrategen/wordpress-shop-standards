<?php

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce Checkout related functionality.
 */
class WooCommerceCheckout {

  /**
   * WooCommerce Checkout initialization method.
   */
  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerceCheckoutSettings');
    // Removes city field from shipping calculator.
    add_filter('woocommerce_shipping_calculator_enable_city', '__return_false');

    if (is_admin() || wp_doing_cron()) {
      return;
    }

    // Adds confirmation email field to the checkout page.
    if (
      get_option('_' . Plugin::L10N . '_checkout_email_confirmation_field') === 'yes' &&
      !is_user_logged_in() &&
      !isset($_GET['woo-paypal-return']) &&
      !self::isAmazonPayV2Checkout()
    ) {
      add_filter('woocommerce_checkout_fields', __CLASS__ . '::addConfirmationEmailCheckoutField');
      add_action('woocommerce_checkout_process', __CLASS__ . '::checkConfirmationEmailField');
    }

    // Add checkout error messages.
    add_filter( 'woocommerce_form_field', __CLASS__ . '::woocommerceFormField', 10, 4 );

    // Remove required fields while using Amazon Pay  V2.
    add_filter('woocommerce_checkout_fields', __CLASS__ . '::removeRequiredFieldsforAmazonPay', 5);
  }

  /**
   * Adds inline error messages to checkout fields when needed.
   *
   * @implements woocommerce_form_field
   *
   * @return string
   */
  public static function woocommerceFormField($field, $key, $args, $value) {
    if (strpos($field, '</label>') === FALSE) {
      return $field;
    }

    $error = [];
    if ($args['required'] ?? FALSE) {
      $error_message = sprintf(__('%s is a required field.', 'woocommerce'), $args['label']);
      $error[] = '<span class="error-required" style="display:none">' . $error_message . '</span>';
    }
    if ($args['validate'] ?? FALSE) {
      $error_message = __('Invalid format', Plugin::L10N);
      $error[]      = '<span class="error-validate" style="display:none">' . $error_message . '</span>';
    }

    if (!empty($error)) {
      $field = substr_replace($field, join('', $error), strpos($field, '</label>'), 0);;
    }
    return $field;
  }

  /**
   * Adds WooCommerce Checkout specific backend settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerceCheckoutSettings(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Checkout settings', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_' . Plugin::L10N . '_checkout_email_confirmation_field',
      'name' => __('Require users to enter billing email address twice', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Adds confirmation email field on the checkout page.
   *
   * @param array $fields
   *   The checkout fields.
   *
   * @return array
   *   The modified checkout fields.
   *
   * @implements woocommerce_checkout_fields
   */
  public static function addConfirmationEmailCheckoutField(array $fields): array {
    $fields['billing']['billing_vat_number']['priority'] = 140;
    $fields['billing']['billing_email']['class'] = ['form-row-first'];
    $fields['billing']['billing_email_confirmation'] = [
      'label' => __('Repeat e-mail address', Plugin::L10N),
      'required' => TRUE,
      'class' => ['form-row-last'],
      'clear' => TRUE,
      'priority' => 120,
    ];
    return $fields;
  }

  /**
   * Check if email field matches the confirmation email one.
   *
   * @implements woocommerce_checkout_process
   */
  public static function checkConfirmationEmailField() {
    if ($_POST['billing_email'] !== $_POST['billing_email_confirmation']) {
      wc_add_notice(__('Your email addresses do not match', Plugin::L10N), 'error');
    }
  }

  /**
   * Check if we are using Amazon V2 in the checkout.
   *
   * @return bool
   */
  public static function isAmazonPayV2Checkout(): bool {
    $is_amzon_pay_active = is_plugin_active('woocommerce-gateway-amazon-payments-advanced/woocommerce-gateway-amazon-payments-advanced.php') ?: FALSE;
    if ($is_amzon_pay_active && isset(WC()->session)) {
      if(version_compare(WC_AMAZON_PAY_VERSION, '2.0', '>=') && WC()->session->get('amazon_checkout_session_id') !== null) {
        return true;
      }
    }
    return false;
  }

  /**
   * Remove Billing address when using Amazon Pay V2.
   *
   * * @return array
   *   checkout fields.
   * 
   * @implements woocommerce_checkout_fields
   */
  public static function removeRequiredFieldsforAmazonPay(array $fields): array {
    if (self::isAmazonPayV2Checkout()) {
        unset($fields['billing']['billing_address_1']);
        return $fields;
    }
    return $fields;
  }
}
