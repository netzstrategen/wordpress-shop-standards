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

    if (is_admin()) {
      return;
    }

    // Adds confirmation email field to the checkout page.
    if (
      get_option('_' . Plugin::L10N . '_checkout_email_confirmation_field') === 'yes' &&
      !is_user_logged_in() &&
      !isset($_GET['woo-paypal-return'])
    ) {
      add_filter('woocommerce_checkout_fields', __CLASS__ . '::addConfirmationEmailCheckoutField');
      add_action('woocommerce_checkout_process', __CLASS__ . '::checkConfirmationEmailField');
    }
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

}
