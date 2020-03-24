<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Seo.
 */

namespace Netzstrategen\ShopStandards;

/**
 * SEO related settings and actions.
 */
class Seo {

  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');

    if (is_admin()) {
      return;
    }

    // Blocks search indexing on search pages.
    add_action('wp_head', __CLASS__ . '::wp_head');
    // Disables Yoast adjacent links.
    add_filter('wpseo_disable_adjacent_rel_links', __CLASS__ . '::wpseo_disable_adjacent_rel_links');

    // To avoid all no-follow links introduced by the products filters sidebar
    // widgets, we convert them into span tags.
    // Removes link tags from list of products filters.
    add_filter('woocommerce_layered_nav_term_html', __CLASS__ . '::woocommerce_layered_nav_term_html', 999, 4);
    // Starts capturing the sidebar content.
    add_action('dynamic_sidebar_before', __CLASS__ . '::dynamic_sidebar_before');
    // Removes link tags from selected products filters tags.
    add_action('dynamic_sidebar_after', __CLASS__ . '::dynamic_sidebar_after');
  }

  /**
   * Adds checkout specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('SEO settings', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_robots_noindex_secondary_product_listings',
      'name' => __('Index first page of paginated products listings only', Plugin::L10N),
      'desc_tip' => __('If checked, noindex meta tag will be added to paginated products listing pages, starting from the second page.', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_wpseo_disable_adjacent_rel_links',
      'name' => __('Disable Yoast SEO adjacent navigation links.', Plugin::L10N),
      'desc_tip' => __('Avoids unwanted rankings of search result URLs as well as paginated listing pages in case the shop\'s product listing is using infinite scrolling or lazy loading to display further products without pagination links.', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Removes link tags from list of products filters.
   *
   * @implements wp_head
   */
  public static function wp_head() {
    $noindex_second_page = get_option(Plugin::L10N . '_robots_noindex_secondary_product_listings');

    if (is_search() || (is_paged() && $noindex_second_page)) {
      echo '<meta name="robots" content="noindex">';
    }
  }

  /**
   * Disables Yoast adjacent links.
   *
   * @implements wpseo_disable_adjacent_rel_links
   */
  public static function wpseo_disable_adjacent_rel_links(): bool {
    return get_option(Plugin::L10N . '_wpseo_disable_adjacent_rel_links');
  }

  /**
   * Removes link tags from list of products filters.
   *
   * @implements woocommerce_layered_nav_term_html
   */
  public static function woocommerce_layered_nav_term_html($term_html, $term, $link, $count) {
    if (strpos($term_html, '<a') !== 0) {
      return $term_html;
    }
    return sprintf('<span class="product-filter-term" data-url="%s">%s</span> <span class="count">(%d)</span>', $link, esc_html($term->name), $count);
  }

  /**
   * Starts capturing the sidebar content.
   *
   * @implements dynamic_sidebar_before
   */
  public static function dynamic_sidebar_before() {
    ob_start();
  }

  /**
   * Removes link tags from selected products filters tags.
   *
   * @implements dynamic_sidebar_after
   */
  public static function dynamic_sidebar_after() {
    $sidebar_content = ob_get_clean();
    $sidebar_content = preg_replace('@(<li\s+class="chosen.*)<a(.*)href="(.*)">(.*)</a></li>@', '$1<span data-url="$3">$4</a></li>', $sidebar_content);
    echo $sidebar_content;
  }

  /**
   * Adds product GTIN to schema.org structured data.
   *
   * @implements woocommerce_structured_data_product
   */
  public static function getProductGtin($data) {
    global $product;

    if (!$gtin = get_post_meta($product->get_id(), '_' . Plugin::PREFIX . '_gtin', TRUE)) {
      return $data;
    }

    switch (strlen(trim($gtin))) {
      case 8:
        $gtin_format_type = 'gtin8';
        break;

      case 13:
        $gtin_format_type = 'gtin13';
        break;

      case 14:
        $gtin_format_type = 'gtin14';
        break;

      default:
        $gtin_format_type = 'gtin12';
    }

    $data[$gtin_format_type] = $gtin;
    return $data;
  }

  /**
   * Adds product brand to schema.org structured data.
   *
   * @implements woocommerce_structured_data_product
   */
  public static function getProductBrand($data) {
    global $product;

    $brand = get_the_terms($product->get_id(), apply_filters(Plugin::PREFIX . '_product_brand_taxonomy', 'pa_marken'));
    if ($brand && !is_wp_error($brand)) {
      $data['brand'] = $brand[0]->name;
    }

    return $data;
  }

}
