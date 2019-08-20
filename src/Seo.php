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
  }

  /**
   * Adds checkout specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings = array_merge($settings, [
      [
        'name' => __('SEO settings', Plugin::L10N),
        'type' => 'title',
      ],
      [
        'id' => '_robots_noindex_secondary_product_listings',
        'type' => 'checkbox',
        'name' => __('Index first page of paginated products listings only', Plugin::L10N),
        'desc_tip' => __('If checked, noindex meta tag will be added to paginated products listing pages, starting from the second page.', Plugin::L10N),
      ],
      [
        'id' => '_wpseo_disable_adjacent_rel_links',
        'type' => 'checkbox',
        'name' => __('Disable Yoast SEO adjacent navigation links.', Plugin::L10N),
        'desc_tip' => __('Avoids unwanted rankings of search result URLs as well as paginated listing pages in case the shop\'s product listing is using infinite scrolling or lazy loading to display further products without pagination links.', Plugin::L10N),
      ],
      [
        'id' => Plugin::L10N,
        'type' => 'sectionend',
      ],
    ]);
    return $settings;
  }

  /**
   * Blocks search indexing on search pages.
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

}
