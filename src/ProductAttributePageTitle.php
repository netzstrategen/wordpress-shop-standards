<?php

namespace Netzstrategen\ShopStandards;

/**
 * Allows optional customization of page title.
 */
class ProductAttributePageTitle {

  public static function admin_init() {
    $attribute_terms = wc_get_attribute_taxonomy_names();
    $group_filter = [];

    foreach ($attribute_terms as $attribute_term) {
      $group_filter[] = [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => $attribute_term,
        ]
      ];
    }

    // Register ACF field.
    ProductAttributePageTitle::register_acf_page_title($group_filter);
  }

  /**
   * Register ACF field.
   */
  public static function register_acf_page_title($group_filter) {
    register_field_group([
      'key' => 'acf_group_page_title',
      'title' => __('Listing Page', Plugin::L10N),
      'fields' => [
        [
          'key' => 'acf_field_page_title',
          'label' => __('Page Title', Plugin::L10N),
          'name' => 'page_title',
          'type' => 'text',
          'instructions' => __('Only changes how the name appears on the page. Leaves the filter drop-down menu alone.', Plugin::L10N),
        ],
      ],
      'location' => $group_filter,
    ]);
  }

  /**
   * Overrides page title on product attribute pages.
   */
  public static function woocommerce_page_title($title) {
    $page_title = get_field('page_title', get_queried_object());
    return $page_title ? esc_html($page_title) : $title;
  }

}
