<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\WooCommerce.
 */

namespace Netzstrategen\ShopStandards;

/**
 * WooCommerce related functionality.
 */
class WooCommerce {

  /**
   * Enables revisions for product descriptions.
   *
   * @implements woocommerce_register_post_type_product
   */
  public static function woocommerce_register_post_type_product($args) {
    $args['supports'][] = 'revisions';
    return $args;
  }

  /**
   * Enables backend to search across product variation IDs, SKUs and ERP/Inventory IDs.
   *
   * When a SKU or ERP/Inventory ID number (possibly of a product variation) is used
   * in the backend search box, then its parent product should be found.
   *
   * @implements posts_search
   */
  public static function posts_search($where, \WP_Query $query) {
    global $pagenow, $wpdb;
    if ($query->query_vars['post_type'] !== 'product' || !$query->query_vars['s']) {
      return $where;
    }
    // Simpler extraction than WP_Query::parse_search_terms() but sufficient
    // for this use-case.
    $search_terms = array_filter(array_map('trim', explode(',', $query->query_vars['s'])));
    $placeholders = implode(',', array_fill(0, count($search_terms), '%s'));

    $search_ids = $wpdb->get_col($wpdb->prepare("
      SELECT IF(p.post_type = 'product', p.ID, p.post_parent) AS post__in
      FROM wp_posts p
      LEFT JOIN wp_postmeta sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
      LEFT JOIN wp_postmeta custom_id ON custom_id.post_id = p.ID AND custom_id.meta_key = '_custom_erp_id'
      WHERE
        -- Product variation IDs are not considered by main search query.
        p.ID IN ($placeholders)
        OR sku.meta_value IN ($placeholders)
        OR custom_id.meta_value IN ($placeholders)
    ", array_merge($search_terms, $search_terms, $search_terms)));

    if ($search_ids) {
      $search_ids = implode(',', $search_ids);
      $where = str_replace('AND (((', "AND ({$wpdb->posts}.ID IN ($search_ids) OR ((", $where);
    }
    return $where;
  }

  /**
   * Adds strike price for variable products above regular product price labels.
   *
   * @see https://github.com/woocommerce/woocommerce/issues/16169
   *
   * @implements woocommerce_variable_sale_price_html
   * @implements woocommerce_variable_price_html
   */
  public static function woocommerce_variable_sale_price_html($price, $product) {
    $sale_prices = [
      'min' => $product->get_variation_price('min', TRUE),
      'max' => $product->get_variation_price('max', TRUE),
    ];
    $regular_prices = [
      'min' => $product->get_variation_regular_price('min', TRUE),
      'max' => $product->get_variation_regular_price('max', TRUE),
    ];
    $regular_price = [wc_price($regular_prices['min'])];
    if ($regular_prices['min'] !== $regular_prices['max']) {
      $regular_price[] = wc_price($regular_prices['max']);
    }
    $sale_price = [wc_price($sale_prices['min'])];
    if ($sale_prices['min'] !== $sale_prices['max']) {
      $sale_price[] = wc_price($sale_prices['max']);
    }
    if (($sale_prices['min'] !== $regular_prices['min'] || $sale_prices['max'] !== $regular_prices['max'])) {
      if ($sale_prices['min'] !== $sale_prices['max'] || $regular_prices['min'] !== $regular_prices['max']) {
        $price = '<del>' . implode('-', $regular_price) . '</del> <ins>' . implode('-', $sale_price) . '</ins>';
      }
    }
    return $price;
  }

  /**
   * Changes sale flash label to display sale percentage.
   *
   * @implements woocommerce_change_sale_to_percentage
   */
  public static function woocommerce_change_sale_to_percentage($output, $post, $product) {
    if ($product->get_type() === 'variation') {
      $sale_percentage = get_post_meta($product->get_parent_id(), '_sale_percentage', TRUE);
    }
    else {
      $sale_percentage = get_post_meta($product->get_id(), '_sale_percentage', TRUE);
    }
    if (((!is_single() && $sale_percentage >= 0) || is_single()) && get_post_meta($product->get_id(), '_custom_hide_sale_percentage_flash_label', TRUE) !== 'yes') {
      $output = '<span class="onsale" data="' . $sale_percentage . '">-' . $sale_percentage . '%</span>';
    }
    else {
      $output = '';
    }
    return $output;
  }

  /**
   * Adds backordering with proper status messages for every product whether
   *   stock managing is enabled or it's available.
   *
   * @implements woocommerce_get_availability
   */
  public static function woocommerce_get_availability($stock, $product) {
    $product->manage_stock = 'yes';
    $product->backorders = 'yes';
    if ($product->managing_stock() && $product->backorders_allowed()) {
      if (!$product->is_in_stock()) {
        $stock['availability'] = __('Out of stock', 'woocommerce');
        $stock['class'] = 'out-of-stock';
      }
      else {
        $stock['availability'] = __('In stock', 'woocommerce');
        $stock['class'] = 'in-stock';
      }
    }
    return $stock;
  }

  /**
   * Sorts products for _sale_percentage.
   *
   * @implements woocommerce_get_catalog_ordering_args
   */
  public static function woocommerce_get_catalog_ordering_args($args) {
    $orderby_value = isset($_GET['orderby']) ? wc_clean($_GET['orderby']) : apply_filters('woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby'));
    if ('sale_percentage' === $orderby_value) {
      $args['orderby'] = 'meta_value_num';
      $args['order'] = 'DESC';
      $args['meta_key'] = '_sale_percentage';
    }
    return $args;
  }

  /**
   * Changes number of displayed products.
   *
   * @implement loop_shop_per_page
   */
  public static function loop_shop_per_page($cols) {
    return 24;
  }

  /**
   * Ensures new product are saved before updating its meta data.
   *
   * New products are still not saved when updated_post_meta hook is called.
   * Since we can not check if the meta keys were changed before running
   * our custom functions (see updateDeliveryTime and updateSalePercentage),
   * we are forcing the post to be saved before updating the meta keys.
   *
   * @implements woocommerce_process_product_meta
   */
  public static function saveNewProductBeforeMetaUpdate($post_id) {
    $product = wc_get_product($post_id);
    $product->save();
  }

  /**
   * Updates product delivery time with the lowest devlivery time between its own variations.
   *
   * If product has variations, its delivery time should be the lowest one between its own variations.
   *
   * @implements updated_post_meta
   */
  public static function updateDeliveryTime($meta_id, $object_id, $meta_key) {
    if ($meta_key !== '_lieferzeit') {
      return;
    }
    $product = wc_get_product($object_id);
    if ($product->product_type === 'variation') {
      $variation_deliveries_ranges = [];
      foreach ($product->parent->get_children() as $variation) {
        $variation_term_id = get_post_meta($variation, '_lieferzeit', TRUE);
        $variation_term_slug = get_term($variation_term_id)->slug;
        // Matches every digits in the delivery time term slug.
        preg_match('/(\d+)/', $variation_term_slug, $variation_delivery_days);
        array_shift($variation_delivery_days);
        if ($variation_delivery_days) {
          $variation_deliveries_ranges[$variation_term_id] = $variation_delivery_days;
        }
      }
      asort($variation_deliveries_ranges);
      update_post_meta($product->get_parent_id(), $meta_key, array_keys($variation_deliveries_ranges)[0]);
    }
  }

  /**
   * Updates sale percentage when regular price or sale price are updated.
   *
   * The plugin woocommerce-advanced-bulk-edit does not invoke the hook 'save_post'
   * when updating the regular price or sale price, so we need to manually
   * calculate the sale percentage whenever post meta fields were updated.
   *
   * @implements updated_post_meta
   */
  public static function updateSalePercentage($check, $product_id, $meta_key, $meta_value) {
    if ($meta_key === '_regular_price' || $meta_key === '_sale_price') {
      $product = wc_get_product($product_id);
      $product_type = $product->get_type();
      if ($product_type === 'variation') {
        $parent_id = $product->get_parent_id();
        static::update_sale_percentage($parent_id, get_post($parent_id));
      }
      elseif ($product_type === 'simple') {
        static::update_sale_percentage($product_id, get_post($product_id));
      }
    }
  }

  /**
   * Creates sale percentage on post save.
   */
  public static function update_sale_percentage($post_id, $post) {
    global $wpdb;
    $product_has_variation = $wpdb->get_var("SELECT ID from wp_posts WHERE post_type = 'product_variation' AND post_parent = $post_id LIMIT 0,1");
    if (get_post_type($post) === 'product') {
      if ($product_has_variation) {
        $where = "WHERE p.post_type = 'product_variation' AND p.post_parent = $post_id";
      }
      else {
        $where = "WHERE p.post_type = 'product' AND p.ID = $post_id";
      }
      $sale_percentage = $wpdb->get_var("SELECT FLOOR((regular_price.meta_value - sale_price.meta_value) / regular_price.meta_value * 100) AS sale_percentage
        FROM wp_posts p
        LEFT JOIN wp_postmeta regular_price ON regular_price.post_id = p.ID AND regular_price.meta_key = '_regular_price'
        LEFT JOIN wp_postmeta sale_price ON sale_price.post_id = p.ID AND sale_price.meta_key = '_sale_price'
        $where
        ORDER BY sale_percentage ASC
        LIMIT 0,1
      ");
      if (!is_null($sale_percentage)) {
        update_post_meta($post_id, '_sale_percentage', $sale_percentage);
        if ($sale_percentage == 100) {
          update_post_meta($post_id, '_sale_percentage', 0);
        }
      }
      else {
        update_post_meta($post_id, '_sale_percentage', 0);
      }
    }
  }

  /**
   * Displays custom fields for single products.
   *
   * @implements woocommerce_product_options_general_product_data
   */
  public static function woocommerce_product_options_general_product_data() {
    // GTIN field.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_text_input([
      'id' => '_custom_gtin',
      'label' => 'GTIN',
      'desc_tip' => 'true',
    ]);
    echo '</div>';

    // ERP/Inventory ID field.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_text_input([
      'id' => '_custom_erp_id',
      'label' => __('ERP/Inventory ID', Plugin::L10N),
      'desc_tip' => 'true',
    ]);
    echo '</div>';

    // Show `sale price only` checkbox.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_checkbox([
      'id' => '_custom_show_sale_price_only',
      'label' => __('Display sale price as normal price', Plugin::L10N),
    ]);
    echo '</div>';

    // Hide `add to cart` button checkbox.
    echo '<div class="options_group show_if_simple show_if_external">';
    woocommerce_wp_checkbox([
      'id' => '_custom_hide_add_to_cart_button',
      'label' => __('Hide add to cart button', Plugin::L10N),
    ]);
    echo '</div>';
  }

  /**
   * Saves custom fields for simple products.
   *
   * @implements woocommerce_process_product_meta
   */
  public static function woocommerce_process_product_meta($post_id) {
    // GTIN field.
    if (isset($_POST['_custom_gtin'])) {
      if ($woocommerce_gtin = $_POST['_custom_gtin']) {
        update_post_meta($post_id, '_custom_gtin', $woocommerce_gtin);
      }
      else {
        delete_post_meta($post_id, '_custom_gtin');
      }
    }

    // ERP/Inventory ID field.
    if (isset($_POST['_custom_erp_id'])) {
      if ($value = $_POST['_custom_erp_id']) {
        update_post_meta($post_id, '_custom_erp_id', $value);
      }
      else {
        delete_post_meta($post_id, '_custom_erp_id');
      }
    }

    // Show `sale price only` checkbox.
    update_post_meta($post_id, '_custom_show_sale_price_only', $_POST['_custom_show_sale_price_only'] ?: 'no');

    // Hide `add to cart` button checkbox.
    update_post_meta($post_id, '_custom_hide_add_to_cart_button', $_POST['_custom_hide_add_to_cart_button'] ?: 'no');
  }


  /**
   * Creates custom fields for product variations.
   *
   * @implements woocommerce_product_after_variable_attributes
   */
  public static function woocommerce_product_after_variable_attributes($loop, $variation_id, $variation) {
    // GTIN field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_custom_variable_gtin[' . $loop . ']',
      'label' => 'GTIN',
      'placeholder' => __('Global Trade Item Number', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_custom_gtin', TRUE),
    ]);
    echo '</div>';

    // ERP/Inventory ID field.
    echo '<div style="clear:both">';
    woocommerce_wp_text_input([
      'id' => '_custom_variable_erp_id[' . $loop . ']',
      'label' => __('ERP/Inventory ID', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_custom_erp_id', TRUE),
    ]);
    echo '</div>';

    // Show `sale price only` checkbox.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_custom_show_sale_price_only',
      'label' => __('Display sale price as normal price:', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_custom_show_sale_price_only', TRUE),
    ]);
    echo '</div>';

    // Hide `add to cart` button checkbox.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_custom_hide_add_to_cart_button',
      'label' => __('Hide add to cart button:', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_custom_hide_add_to_cart_button', TRUE),
    ]);
    echo '</div>';

    // Insufficient variant images button checkbox.
    echo '<div style="clear:both">';
    woocommerce_wp_checkbox([
      'id' => '_custom_insufficient_variant_images',
      'label' => __('Variation has insufficient images', Plugin::L10N),
      'value' => get_post_meta($variation->ID, '_custom_insufficient_variant_images', TRUE),
      'desc_tip' => __('Allows this product to be identified and possibly be excluded by other processes and plugins (e.g. a custom filter for product feeds). Enabling this option has no effect on the output (by default).', Plugin::L10N),
    ]);
    echo '</div>';
  }

  /**
   * Saves custom fields for product variatons.
   *
   * @implements woocommerce_save_product_variation
   */
  public static function woocommerce_save_product_variation($post_id) {
    if (isset($_POST['variable_sku'], $_POST['variable_post_id']) && (isset($_POST['_custom_variable_gtin']) || isset($_POST['_custom_variable_erp_id']))) {
      $variable_sku = $_POST['variable_sku'];
      $variable_post_id = $_POST['variable_post_id'];
      foreach ($variable_post_id as $variation_id) {
        $gtin = $_POST['_custom_variable_gtin'] ?: '';
        $erp_id = $_POST['_custom_variable_erp_id'] ?: '';
        foreach ($variable_sku as $j => $sku) {
          // GTIN field.
          $variation_id = (int) $variable_post_id[$j];
          if (isset($gtin[$j])) {
            update_post_meta($variation_id, '_custom_gtin', $gtin[$j]);
          }

          // ERP/Inventory ID field.
          if (isset($erp_id[$j])) {
            update_post_meta($variation_id, '_custom_erp_id', $erp_id[$j]);
          }

          // Show `sale price only` checkbox.
          if ($hide_sale_price = $_POST['_custom_show_sale_price_only'] ?: 'no') {
            update_post_meta($variation_id, '_custom_show_sale_price_only', $hide_sale_price);
          }

          // Hide `add to cart` button checkbox.
          if ($hide_add_to_cart_button = $_POST['_custom_hide_add_to_cart_button'] ?: 'no') {
            update_post_meta($variation_id, '_custom_hide_add_to_cart_button', $hide_add_to_cart_button);
          }

          // Insufficient variant images button checkbox.
          if ($hide_add_to_cart_button = $_POST['_custom_insufficient_variant_images'] ?: 'no') {
            update_post_meta($variation_id, '_custom_insufficient_variant_images', $hide_add_to_cart_button);
          }
        }
      }
    }
  }

}
