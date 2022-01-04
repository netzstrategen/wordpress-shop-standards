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
    // Logs consent to defective or used product when product is added to cart.
    add_action('woocommerce_add_to_cart', function () {
      ProductDefects::logConsent();
    });

    add_action('wp', function () {
      $show_value = get_post_meta(get_the_ID(), '_' . Plugin::PREFIX . '_show_product_defects_consent');
      if ($show_value && $show_value[0] === 'yes') {
        $show = TRUE;
      }
      else {
        $show = FALSE;
      }
      if ($show || has_term(get_option(Plugin::PREFIX . '_add_category_field'), 'product_cat', get_the_ID())) {
        add_action('woocommerce_before_add_to_cart_button', __CLASS__ . '::displayCheckbox');
      }
    });

    if (!is_admin()) {
      return;
    }

    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');
  }

  /**
   * Logs consent to defective or used product.
   */
  public static function logConsent() {
    $uploads_dir = wp_upload_dir()['basedir'] . '/' . Plugin::PREFIX;
    $logFile = $uploads_dir . '/defects-consent.log';
    if (!dir($uploads_dir)) {
      mkdir($uploads_dir);
    }
    $data = [
      'timestamp' => date_i18n('c'),
      'user' => get_current_user_id(),
      'referrer' => $_SERVER['HTTP_REFERER'],
      'consent' => $_POST['used-goods-consent'],
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
    $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($logFile, $data . "\n", 8);
  }

  /**
   * Displays the checkbox and agreement text.
   */
  public static function displayCheckbox() {
    echo '<div class="checkbox-container">
      <input required data-checkbox-toggle class="checkbox-box" type="checkbox" id="used-goods-consent" name="used-goods-consent" value="used-goods-consent-true">
      <b>' . get_option(Plugin::PREFIX . '_defect_title_field') . '
        <span class="checkbox-read-more" data-checkbox-detail-expand>' . __('Read More', Plugin::L10N) . '...</span>
      </b>
      <div class="checkbox-detail">
       <p>' . get_option(Plugin::PREFIX . '_defect_desc_field') . '</p>
       <p>' . get_option(Plugin::PREFIX . '_defect_attr_desc_field') . '</p>
       <p>-(z.B.: <span class="zustand"></span>)</p>
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
