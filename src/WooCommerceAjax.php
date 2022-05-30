<?php

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce Ajax related functionality.
 */
class WooCommerceAjax {

    /**
     * WooCommerce Ajax initialization method.
     */
    public static function init() 
    {


        add_action('wp_ajax_woocommerceAjaxAddToCart', __CLASS__ . '::woocommerceAjaxAddToCart');
        add_action('wp_ajax_nopriv_woocommerceAjaxAddToCart', __CLASS__ . '::woocommerceAjaxAddToCart');
        add_filter('woocommerce_add_to_cart_fragments', __CLASS__ . '::ajaxAddToCartNoticeFragments');
        // add_filter('woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\WooCommerceAjax::ajaxAddToCartAccessoriesFragments');
    }

    /**
     * WooCommerce Ajax initialization method.
     * 
     * @return object
     */
    public static function woocommerceAjaxAddToCart() 
    {
        $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
        $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
        $variation_id = absint($_POST['variation_id']);
        $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
        $product_status = get_post_status($product_id);

        if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) {
            do_action('woocommerce_ajax_added_to_cart', $product_id);
            wc_add_to_cart_message(array($product_id => $quantity), true);
            \WC_AJAX::get_refreshed_fragments();
        } else {
            $data = array(
                'error' => true,
                'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
            );
            echo wp_send_json($data);
        }

        wp_die();
    }

    /**
     * WooCommerce Ajax add notice in Woocommerce fragments.
     * 
     * @return object
     */
    public static function ajaxAddToCartNoticeFragments($fragments) 
    { 
        $all_notices = WC()->session->get('wc_notices', array());
        $notice_types = apply_filters('woocommerce_notice_types', array( 'error', 'success', 'notice' ));
   
        ob_start();
        foreach ($notice_types as $notice_type) {
            if (wc_notice_count($notice_type) > 0) {
                wc_get_template("notices/{$notice_type}.php", array(
                    'messages' => array_filter($all_notices[$notice_type]),
                    'notices' => array_filter($all_notices[$notice_type])
                ));
            }
        }

        $fragments['notices_html'] = ob_get_clean();
        wc_clear_notices();
        return $fragments;
    }

    // /**
    //  * WooCommerce Ajax add related accesories in Woocommerce fragments.
    //  * 
    //  * @return object
    //  */
    // public static function ajaxAddToCartAccessoriesFragments($fragments) 
    // {
    //     $accessories_html = '';
    //     if (!isset($_POST['product_id'])) {
    //         return $fragments;
    //     }

    //     $related_accessories_ids = get_field('field_group_related_accessories', $_POST['product_id']);
    //     $related_accessories_ids = apply_filters('related-accessories/get_related_accessories_ids', $related_accessories_ids);

    //     if (!$related_accessories_ids) {
    //         return $fragments;
    //     }

    //     $related_accessories = Woocommerce::buildRelatedProductsView($related_accessories_ids);
    //     ob_start();

    //     Plugin::renderTemplate(
    //         ['templates/related-accessories.php'], [
    //         'related_accessories' => $related_accessories,
    //         'is_notice_template' => TRUE ]
    //     );
    //     $accessories_html = ob_get_clean();

    //     $fragments['accessories_html'] = $accessories_html;
    //     return $fragments;
    // }
}
