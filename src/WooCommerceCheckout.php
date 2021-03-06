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

    // Ensure shipping and billing salutation align for company orders.
    add_action('woocommerce_after_checkout_validation', __CLASS__ . '::ensureCompanyShipping', 10, 2);
    add_action('woocommerce_checkout_update_order_review', __CLASS__ . '::ensureCompanyShipping', 10, 2);
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
   * Ensures the billing and shipping salutation align for company orders.
   *
   * Since two hooks, with different parameters are used, check for a combination
   * of post data and the errors object to identify how to display the error.
   *
   * @implements woocommerce_checkout_update_order_review
   * @implements woocommerce_after_checkout_validation
   *
   * @param array $data
   * @param \WP_error|null $errors
   */
  public static function ensureCompanyShipping($data = [], \WP_error $errors = NULL): void {
    if (!$data) {
      return;
    }
    if (!empty($_POST['post_data'])) {
      parse_str($_POST['post_data'], $form_data);
      $data = $form_data;
    }
    if (!empty($data['ship_to_different_address'])) {
      $billing_salutation = wc_clean(wp_unslash($data['billing_salutation'] ?? ''));
      $billing_vat_number = wc_clean(wp_unslash($data['billing_vat_number'] ?? ''));
      $shipping_salutation = wc_clean(wp_unslash($data['shipping_salutation'] ?? ''));

      if ($billing_salutation === 'Company' && $billing_vat_number && $billing_salutation !== $shipping_salutation) {
        $error = __('The order must be shipped to a company address. If a VAT is filled.', Plugin::L10N);
        if ($errors) {
          $errors->add('shipping', $error);
        }
        else {
          wc_add_notice($error, 'error');
        }
      }
    }
  }

}
