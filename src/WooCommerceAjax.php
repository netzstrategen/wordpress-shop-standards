<?php

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce Ajax related functionality.
 */
class WooCommerceAjax {

  /**
   * WooCommerce Ajax initialization method.
   */
  public static function init() {
    add_action('wp_ajax_woocommerceAjaxAddToCart', __CLASS__ . '::woocommerceAjaxAddToCart');
    add_action('wp_ajax_nopriv_woocommerceAjaxAddToCart', __CLASS__ . '::woocommerceAjaxAddToCart');
    add_filter('woocommerce_add_to_cart_fragments', __CLASS__ . '::ajaxAddToCartNoticeFragments');
  }

  /**
   * WooCommerce Ajax initialization method.
   */
  public static function woocommerceAjaxAddToCart() {
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
      wp_die();
    }
    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = absint($_POST['variation_id']);
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', TRUE, $product_id, $quantity);
    $product_type = $_POST['product_type'];
    $product_status = get_post_status($product_id);

    if ($product_type == 'variable' && $passed_validation) {
      $passed_validation = $variation_id != 0 ? TRUE : FALSE;
    }
    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) {
      do_action('woocommerce_ajax_added_to_cart', $product_id);
      wc_add_to_cart_message([$product_id => $quantity], TRUE);
      \WC_AJAX::get_refreshed_fragments();
    }
    else {
      $product = wc_get_product($product_id);
      wc_add_notice(sprintf(__('Please choose product options by visiting <a href="%1$s" title="%2$s">%2$s</a>.', 'woocommerce'), esc_url(get_permalink($product_id)), esc_html($product->get_name())), 'error');
      \WC_AJAX::get_refreshed_fragments();
    }

    wp_die();
  }

  /**
   * WooCommerce Ajax add notice in Woocommerce fragments.
   * @return object
   */
  public static function ajaxAddToCartNoticeFragments($fragments) {
    $all_notices = WC()->session->get('wc_notices', []);
    $notice_types = apply_filters('woocommerce_notice_types', [
      'error', 'success', 'notice'
    ]);
    ob_start();
    foreach ($notice_types as $notice_type) {
      if (wc_notice_count($notice_type) > 0) {
        wc_get_template("notices/{$notice_type}.php", [
          'messages' => array_filter($all_notices[$notice_type]),
          'notices' => array_filter($all_notices[$notice_type])
        ]);
      }
    }

    $fragments['notices_html'] = ob_get_clean();
    wc_clear_notices();
    return $fragments;
  }

}
