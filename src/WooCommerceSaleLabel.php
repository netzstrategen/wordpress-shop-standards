<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerceSaleLabel.
 */

/**
 * Automatic conditional assignment of sale categories.
 */
namespace Netzstrategen\ShopStandards;

class WooCommerceSaleLabel {

  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');

    // Assigns sale category conditionally on product update.
    if (get_option('_' . Plugin::L10N . '_sale_auto_label_enabled') === 'yes') {
      add_action('woocommerce_update_product', __CLASS__ . '::woocommerce_update_product');
    }
  }

  /**
   * Adds woocommerce specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Automatic sale category assignment', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_' . Plugin::L10N . '_sale_auto_label_enabled',
      'name' => __('Enable', Plugin::L10N),
      'show_if_checked' => 'option',
    ];
    $settings[] = [
      'type' => 'multiselect',
      'id' => '_' . Plugin::L10N . '_sale_auto_label_delivery_times',
      'name' => __('Eligible delivery times', Plugin::L10N),
      'options' => WooCommerce::getTaxonomyTermsAsSelectOptions('product_delivery_times'),
      'css' => 'height:auto',
      'custom_attributes' => [
        'size' => wp_count_terms('product_delivery_times', ['hide_empty'=> false, 'parent' => 0]),
      ],
    ];
    $settings[] = [
      'type' => 'select',
      'id' => '_' . Plugin::L10N . '_sale_auto_label_category',
      'name' => __('Sale category to assign', Plugin::L10N),
      'options' => WooCommerce::getTaxonomyTermsAsSelectOptions(ProductsPermalinks::TAX_PRODUCT_CAT),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Assigns sale category conditionally on product update.
   *
   * @implements woocommerce_update_product
   */
  public static function woocommerce_update_product($product_id) {
    $product = wc_get_product($product_id);
    $sale_category_id = (int) get_option('_' . Plugin::L10N . '_sale_auto_label_category');

    if (!$product || !$sale_category_id) {
      return;
    }

    $current_category_ids = $product->get_category_ids();

    if ($product->is_type('variable')) {
      $variations = $product->get_available_variations();

      foreach ($variations as $variation) {
        $add_sale_category = static::checkAddToSaleCategory($variation['variation_id']);
        if ($add_sale_category) {
          break;
        }
      }
    }
    else {
      $add_sale_category = static::checkAddToSaleCategory($product_id, $product);
    }

    if ($add_sale_category) {
      $updated_category_ids = array_unique(array_merge($current_category_ids, [$sale_category_id]));
    }
    else {
      $updated_category_ids = array_diff($current_category_ids, [$sale_category_id]);
    }

    if ($updated_category_ids != $current_category_ids) {
      // Using the WordPress way of saving the terms for the product, because $product->set_category_ids() didn't work somehow.
      wp_set_object_terms($product_id, $updated_category_ids, 'product_cat');
    }
  }

  /**
   * Checks conditions required to add the sale category to a product.
   *
   * @param int $product_id
   *   Product unique identifier.
   * @param \WC_Product $product
   *   Product to be checked.
   *
   * @return bool
   *   TRUE if product is eligible to be in sale category.
   */
  public static function checkAddToSaleCategory($product_id, $product = FALSE) {
    if (!$product) {
      $product = wc_get_product($product_id);
    }

    $is_variation = $product->get_type() === 'variation';
    if ($is_variation) {
      $parent_id = $product->get_parent_id();
    }

    // Product needs to be in stock.
    $is_in_stock = $product->is_in_stock();

    // Delivery time needs to be in defined set of eligible options.
    $eligible_delivery_times = get_option('_' . Plugin::L10N . '_sale_auto_label_delivery_times');
    $delivery_time = get_post_meta($product_id, '_lieferzeit', TRUE);
    $has_eligible_delivery_time = in_array($delivery_time, $eligible_delivery_times);

    // Product needs to be on sale.
    $is_on_sale = $product->is_on_sale();

    // Sale label needs to be shown.
    $sale_label_is_visible = get_post_meta($is_variation ? $parent_id : $product_id, '_' . Plugin::L10N . '_hide_sale_percentage_flash_label', TRUE) !== 'yes';

    // Discount needs to be at least the minimum sale percentage required to display the sale label.
    $sale_percentage = (int) get_post_meta($is_variation ? $parent_id : $product_id, '_sale_percentage', TRUE);
    $minimum_sale_percentage = (int) get_option('_minimum_sale_percentage_to_display_label', 10);
    $is_discounted_enough = $sale_percentage >= $minimum_sale_percentage;

    // Option "show sale price as regular price" needs to be deactivated.
    $option_is_deactivated = get_post_meta($is_variation ? $parent_id : $product_id, '_' . Plugin::L10N . '_show_sale_price_only', TRUE) !== 'yes';

    // Check all conditions.
    $add_sale_category = $is_in_stock
                         && $has_eligible_delivery_time
                         && $is_on_sale
                         && $sale_label_is_visible
                         && $is_discounted_enough
                         && $option_is_deactivated;

    return $add_sale_category;
  }

}
