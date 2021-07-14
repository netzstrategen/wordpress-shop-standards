<?php

namespace Netzstrategen\ShopStandards\ProductFilters;

/**
 * Widgets functionality.
 */
class DeliveryTime {

  /**
   * Registers products filter widgets supporting delivery time.
   *
   * @implemets widgets_init
   */
  public static function widgets_init() {
    // Registers widget to filter products by delivery time.
    register_widget(__NAMESPACE__ . '\WidgetFilterDeliveryTime');

    // Overrides layered nav woocommerce widgets with new ones supporting
    // woocommerce-german-market delivery time taxonomy terms to be used
    // as product filters.
    unregister_widget('WC_Widget_Layered_Nav_Filters');
    unregister_widget('WC_Widget_Layered_Nav');
    register_widget(__NAMESPACE__ . '\WidgetLayeredNav');
    register_widget(__NAMESPACE__ . '\WidgetLayeredNavFilters');
  }

  /**
   * Adds custom query variable to filter products by delivery time.
   *
   * @implements query_vars
   */
  public static function query_vars($vars) {
    $vars[] = 'delivery_time';
    return $vars;
  }

  /**
   * Modifies the main query to allow filtering products by delivery time.
   *
   * @implements pre_get_posts
   */
  public static function pre_get_posts($query) {
    if (is_admin() || !$query->is_main_query() || !$filter_values = $_GET['delivery_time'] ?? []) {
      return;
    }
    $filter_values = array_filter(array_map('absint', explode(',', wp_unslash($filter_values))));
    $meta_query = [
      'relation' => 'AND',
      [
        'key' => '_lieferzeit',
        'value' => $filter_values,
        'compare' => 'IN',
      ],
      [
        'relation' => 'OR',
        [
          'key' => '_shop-standards_back_in_stock_date',
          'value' => date('Y-m-d'),
          'compare' => '<=',
        ],
        [
          'key' => '_shop-standards_back_in_stock_date',
          'compare' => 'NOT EXISTS',
        ]
      ]
    ];

    $query->set('meta_query', $meta_query);
  }

  /**
   * Adds the passed argument as query parameter to all matched hrefs.
   *
   * @param string $html_filter
   *   The content to perform the transformation on.
   * @param string $filter_name
   *   The query parameter to add.
   *
   * @return string
   *   Content modified to include given query parameter.
   */
  public static function addFilterToNavLinks(string $html_filter, string $filter_name): string {
    if ($filter_args = $_GET[$filter_name] ?? []) {
      $filter_args = array_filter(array_map('absint', explode(',', wp_unslash($filter_args))));
    }
    // Return early if filter is currently not active.
    if (!$filter_args) {
      return $html_filter;
    }
    // Add query parameter to all found hrefs.
    $html_filter = preg_replace_callback('@href="(.+?[^"])"@', function ($match) use ($filter_name, $filter_args) {
      $link = 'href="' . esc_url(add_query_arg($filter_name, implode(',', $filter_args), $match[1])) . '"';
      return $link;
    }, $html_filter);

    return $html_filter;
  }

}
