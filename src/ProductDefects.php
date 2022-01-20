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

  const FIELD_PRODUCTS_CATEGORIES = Plugin::PREFIX . '_product_defects_categories';
  const FIELD_TITLE_TEXT = Plugin::PREFIX . '_product__defects_title_text';
  const FIELD_DESCRIPTION_TEXT = Plugin::PREFIX . '_product_defects_description_text';
  const FIELD_ATTRIBUTE_TEXT = Plugin::PREFIX . '_product_defects_attr_text';
  const FIELD_PRODUCT_ATTRIBUTE = Plugin::L10N . '_product_defects_attribute_id';

  /**
   * Checkbox consent initialization method.
   */
  public static function init() {
    add_action('wp', __CLASS__ . '::should_display_product_checkbox');

    add_filter('woocommerce_available_variation', __CLASS__ . '::pass_variation_attribute', 10, 3);

    if (!is_admin()) {
      return;
    }

    if (function_exists('register_field_group')) {
      ProductDefects::register_acf_status_attribute();
    }
    add_action('woocommerce_variation_options', __CLASS__ . '::add_variant_option', 10, 3);
    add_action('woocommerce_update_product_variation', __CLASS__ . '::update_variant_option', 10, 1);
    add_action('woocommerce_save_product_variation', __CLASS__ . '::save_product_defective_type', 10, 1);
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');
  }

  /**
   * Determines if defective or used checkbox consent should be shown based on settings.
   *
   * @implemements wp
   */
  public static function should_display_product_checkbox() {
    $post = get_post();
    if (!$post || $post->post_type !== 'product') {
      // Make sure the current post is a valid product.
      return;
    }

    $marked_option = get_post_meta($post->ID, WooCommerce::FIELD_SHOW_PRODUCT_DEFECTS_CONSENT, true);
    $display_check = $marked_option === 'yes';
    if (!$display_check) {
      // Execute this query only if the first condition was not met.
      $categories_selected = get_option(self::FIELD_PRODUCTS_CATEGORIES);
      $has_categories = !empty($categories_selected) && has_term($categories_selected, ProductsPermalinks::TAX_PRODUCT_CAT, $post->ID);
    }

    if ($display_check || $has_categories) {
      add_action('woocommerce_before_add_to_cart_button', __CLASS__ . '::woocommerce_before_add_to_cart_button');
      // Logs consent to defective or used product when product is added to cart.
      add_action('woocommerce_add_to_cart', __CLASS__ . '::log_consent_file');
    }
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
    $product_attribute = get_option(self::FIELD_PRODUCT_ATTRIBUTE);
    if(!empty($product_attribute)){
      /** @var \WC_Product $product */
      $product = wc_get_product();
      if ($product->is_type('variable')) {
        // This is to initially set the status variable before js sets it dynamically on variant selection.
        $attribute_value = wc_get_product()->get_variation_attributes()[$product_attribute][0];
      }
      else {
        $attribute_value = wc_get_product()->get_attribute($product_attribute);
      }
    }
    echo '<div class="product-defects__checkbox-container">
    <b>' . get_option(self::FIELD_TITLE_TEXT) . '</b>
    <div class="product-defects__checkbox-detail">
     <p>' . get_option(self::FIELD_DESCRIPTION_TEXT) . '</p>
     <p>
     <input required data-checkbox-toggle class="checkbox-box" type="checkbox" id="used-goods-consent" name="used-goods-consent" value="used-goods-consent-true"/>
     <span>' . get_option(self::FIELD_ATTRIBUTE_TEXT) . '</span>
     </p>
     <span class="product-defects__attribute">';
    echo $attribute_value ?? '';
    echo '</span>
    </div>
    </div>';
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
        'type' => 'multiselect',
        'title' => __('Categories', Plugin::L10N),
        'desc' => __('Add a category to product defects consent.', Plugin::L10N),
        'id' => self::FIELD_PRODUCTS_CATEGORIES,
        'options' => WooCommerce::getTaxonomyTermsAsSelectOptions(ProductsPermalinks::TAX_PRODUCT_CAT, ['orderby' => 'name']),
        'multiple' => 1,
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
        'title' => __('Description before product attribute text', Plugin::L10N),
        'id' => self::FIELD_ATTRIBUTE_TEXT,
      ],
      [
        'type' => 'select',
        'id' => self::FIELD_PRODUCT_ATTRIBUTE,
        'name' => __('Product attribute to display', Plugin::L10N),
        'options' => ['' => __('None', Plugin::L10N)] + WooCommerce::getAvailableAttributes(),
      ],
      [
        'type' => 'sectionend',
        'id' => Plugin::L10N . '_product_defects_section',
      ]
    ];

    return array_merge($settings, $module_settings);
  }

  /**
   * Adds defective/used goods checkmark to variant level.
   *
   * @implemements woocommerce_variation_options
   */
  public static function add_variant_option($loop, $variation_data, $variation) {
    $_defect = get_post_meta($variation->ID, '_defective', TRUE);
    ?>
    <label>
      <input type="checkbox" id="_defective" class="checkbox variable_is_defective" name="variable_is_defective[<?php echo $loop; ?>]" <?php checked(isset($_defect) ? $_defect : '', 'yes'); ?> />
      <?php _e('Defective/Used', Plugin::PREFIX); ?>
      <a class="tips" data-tip="<?php esc_attr_e('Only products with this marker will be treated as “used or defective” in the context of the EU used or defective goods consent notice law of January 1, 2022.', Plugin::PREFIX); ?>" href="#">[?]</a>
    </label>
    <?php
  }

  /**
   * Updates the variant defective option.
   *
   * @wp-hook woocommerce_update_product_variation
   */
  public static function update_variant_option($var_id) {
    if (!isset($_POST['variable_post_id'])) {
      return;
    }
    $variable_post_id = $_POST['variable_post_id'];
    $max_loop = max(array_keys($_POST['variable_post_id']));
    for ($i = 0; $i <= $max_loop; $i++) {
      if (!isset($variable_post_id[$i])) {
        continue;
      }
      $variable_is_defective = isset($_POST['variable_is_defective']) ? $_POST['variable_is_defective'] : [];
      $variation_id = absint($variable_post_id[$i]);
      if ($variation_id == $var_id) {
        $is_defective = isset($variable_is_defective[$i]) ? 'yes' : 'no';
        update_post_meta($var_id, '_defective', $is_defective);
      }
    }
  }

  /**
   * Updates the variant defective option on post save.
   *
   * @wp-hook save_post
   */
  public static function save_product_defective_type($id) {
    if (isset($_REQUEST['variable_is_defective'])) {
      update_post_meta($id, '_defective', 'yes');
    }
    else {
      update_post_meta($id, '_defective', 'no');
    }
  }

  /**
   * Register ACF field.
   */
  public static function register_acf_status_attribute() {
    register_field_group([
      'key' => 'acf_group_status_defect',
      'title' => __('Defective/Used Consent', Plugin::L10N),
      'fields' => [
        [
          'key' => 'acf_field_defective',
          'label' => __('Defective/Used', Plugin::L10N),
          'name' => Plugin::L10N . 'product_defect',
          'type' => 'true_false',
          'instructions' => __('Adds the consent text to used or defective variants.', Plugin::L10N),
        ],
      ],
      'location' => [
        [
          [
            'param' => 'taxonomy',
            'operator' => '==',
            'value' => 'pa_zustand',
          ]
        ]
      ],
    ]);
  }

  /**
   * Passes the variation attribute to jquery object.
   */
  public static function pass_variation_attribute($data, $product, $variation) {
    $data['attribute_pa_zustand_name'] = get_term_by('slug', get_post_meta($variation->get_id(), 'attribute_pa_zustand', TRUE), 'pa_zustand')->name;
    // @TODO: This feature is not necessary right now but the following does not return the true or false variable. Would be nice to keep supporting code anyways in case we need it in future.
    $data['defective'] = get_post_meta($variation->get_id(), '_defective', TRUE);

    return $data;
  }

}
