<?php

namespace Netzstrategen\ShopStandards;

/**
 * Allows optional customization of page title.
 */
class ProductAttributePageTitle {

  public static function init() {
    $attribute_terms = wc_get_attribute_taxonomy_names();
    $group_filter = [];

    foreach( $attribute_terms as $attribute_term ) {
      $group_filter[] = [[
        'param'    => 'taxonomy',
        'operator' => '==',
        'value'    => $attribute_term,
      ]];
    }

    // Register ACF field.
    ProductAttributePageTitle::register_acf_page_title($group_filter);

    // Front-end output of title.
    add_filter('woocommerce_page_title', __CLASS__.'::woocommerce_page_title');
  }

  /**
   * Register ACF field.
   */
  public static function register_acf_page_title($group_filter) {
    if (function_exists('register_field_group')) {
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
            'required' => 0,
            'conditional_logic' => 0,
          ],
        ],
        'location' => $group_filter,
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
      ]);
    }
  }

  /**
   * Front-end output of title.
   */
  public static function woocommerce_page_title($title) {
    $page_title = get_field('page_title', get_queried_object());
    return $page_title ? esc_html($page_title) : $title;
  }

}
