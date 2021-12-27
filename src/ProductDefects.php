<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\UsedGoods.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Used or defective goods user consent checkbox functionality.
 */
class ProductDefects {

  /**
   * Checkbox consent initialization method.
   */
  public static function init() {
    add_action('woocommerce_add_to_cart', function () {
      // @TODO: Log consent using this post variable...
      //var_dump($_POST['used-goods-consent']);
    });

    add_action('wp', function () {
      if (has_term(get_option(Plugin::PREFIX . '_add_category_field'), 'product_cat', get_the_ID())) {
        add_action('woocommerce_before_add_to_cart_button', __CLASS__ . '::displayCheckbox');
      }
    });

    if (!is_admin()) {
      return;
    }

    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');
  }

  /**
   * Displays the checkbox and agreement text.
   */
  public static function displayCheckbox() {
    global $product;
    if ($product->is_type('variable')) {
      $status = wc_get_product()->get_variation_attributes()['pa_zustand'][0];
    }
    echo '<div class="checkbox-container">
      <input required data-checkbox-toggle class="checkbox-box" type="checkbox" id="used-goods-consent" name="used-goods-consent" value="used-goods-consent-true">
      <b>' . get_option(Plugin::PREFIX . '_defect_title_field') . '
        <span class="checkbox-read-more" data-checkbox-detail-expand>' . __('Read More', Plugin::L10N) . '...</span>
      </b>
      <div class="checkbox-detail">
       <p>' . get_option(Plugin::PREFIX . '_defect_desc_field') . '</p>
       <p>' . get_option(Plugin::PREFIX . '_defect_attr_desc_field') . '</p>
       <p>-(z.B.: ' . $status . ')</p>
      </div>
    </div>';
  }

  /**
   * Adds checkout specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('Product Defects Settings', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'multiselect',
      'title' => __('Categories', Plugin::L10N),
      'desc' => __('Add a category to product defects consent.', Plugin::L10N),
      'id' => Plugin::PREFIX . '_add_category_field',
      'options' => WooCommerce::getTaxonomyTermsAsSelectOptions('product_cat'),
      'multiple' => 1,
    ];
    $settings[] = [
      'type' => 'text',
      'title' => __('Title text', Plugin::L10N),
      'id' => Plugin::PREFIX . '_defect_title_field',
    ];
    $settings[] = [
      'type' => 'text',
      'title' => __('Description text', Plugin::L10N),
      'id' => Plugin::PREFIX . '_defect_desc_field',
    ];
    $settings[] = [
      'type' => 'text',
      'title' => __('Description before status attribute text', Plugin::L10N),
      'id' => Plugin::PREFIX . '_defect_attr_desc_field',
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

}
