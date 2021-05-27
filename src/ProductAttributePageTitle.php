<?php

namespace Netzstrategen\ShopStandards;

/**
 * Enables customization of page title.
 */
class ProductAttributePageTitle {
  public static function register_acf_page_title_init() {
    add_filter( 'acf/location/rule_types', __CLASS__ . '::acf_types');
    add_filter( 'acf/location/rule_values/wc_prod_attr', __CLASS__ . '::acf_rules');
    add_filter( 'acf/location/rule_match/wc_prod_attr', __CLASS__ . '::acf_match', 10, 3);

    // Adds alternative title to page categorization.
    $attribute_terms = wc_get_attribute_taxonomy_names();
    $group_filter = [];

    foreach( $attribute_terms as $attribute_term ) {
      $group_filter[] = [[
        'param'    => 'wc_prod_attr',
        'operator' => '==',
        'value'    => $attribute_term,
      ]];
    }

    ProductAttributePageTitle::register_acf_page_title($group_filter);
  }

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
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => [
            'width' => '',
            'class' => '',
            'id' => '',
          ],
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'maxlength' => '',
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

  /**
   * Adds optional custom page title for attribute terms.
   */
  public static function acf_types($choices){
    $choices[ __('Listing Page','acf') ]['wc_prod_attr'] = 'WC Product Attribute';
    return $choices;
  }
  public static function acf_rules($choices){
    foreach (wc_get_attribute_taxonomies() as $attr) {
      $pa_name = wc_attribute_taxonomy_name($attr->attribute_name);
      $choices[$pa_name] = $attr->attribute_label;
    }
    return $choices;
  }
  public static function acf_match($match, $rule, $options){
    if (isset($options['taxonomy'])) {
      if ('==' === $rule['operator']) {
        $match = $rule['value'] === $options['taxonomy'];
      }
      elseif ('!=' === $rule['operator']) {
        $match = $rule['value'] !== $options['taxonomy'];
      }
    }
    return $match;
  }
}
