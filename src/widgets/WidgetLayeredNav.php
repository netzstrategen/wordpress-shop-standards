<?php

namespace Netzstrategen\ShopStandards\Widgets;

/**
 * Widget layered nav class.
 */
class WidgetLayeredNav extends \WC_Widget_Layered_Nav {

  /**
   * Show list based layered nav.
   *
   * @param array $terms
   *   Terms.
   * @param string $taxonomy
   *   Taxonomy.
   * @param string $query_type
   *   Query Type.
   *
   * @return bool
   *   Returns TRUE if the layered nav list is displayed, otherwise FALSE.
   */
  protected function layered_nav_list($terms, $taxonomy, $query_type) {
    // List display.
    echo '<ul class="woocommerce-widget-layered-nav-list">';

    $term_counts = $this->get_filtered_term_product_counts(wp_list_pluck($terms, 'term_id'), $taxonomy, $query_type);
    $_chosen_attributes = \WC_Query::get_layered_nav_chosen_attributes();
    $found = FALSE;

    foreach ($terms as $term) {
      $current_values = isset($_chosen_attributes[$taxonomy]['terms']) ? $_chosen_attributes[$taxonomy]['terms'] : [];
      $option_is_set = in_array($term->slug, $current_values, TRUE);
      $count = isset($term_counts[$term->term_id]) ? $term_counts[$term->term_id] : 0;

      // Skip the term for the current archive.
      if ($this->get_current_term_id() === $term->term_id) {
        continue;
      }

      // Only show options with count > 0.
      if (0 < $count) {
        $found = TRUE;
      }
      elseif (0 === $count && !$option_is_set) {
        continue;
      }

      $filter_name = 'filter_' . str_replace('pa_', '', $taxonomy);
      // WPCS: input var ok, CSRF ok.
      $current_filter = isset($_GET[$filter_name]) ? explode(',', wc_clean(wp_unslash($_GET[$filter_name]))) : [];
      $current_filter = array_map('sanitize_title', $current_filter);

      if (!in_array($term->slug, $current_filter, TRUE)) {
        $current_filter[] = $term->slug;
      }

      // Adds support for woocommerce-german-market delivery time taxonomy terms
      // to be used as product filters.
      $link = remove_query_arg($filter_name, $this->get_current_page_url());
      $filter_delivery_time = isset($_GET['delivery_time']) ? array_filter(array_map('absint', explode(',', wp_unslash($_GET['delivery_time'])))) : [];
      if (!empty($filter_delivery_time)) {
        $link = add_query_arg('delivery_time', implode(',', $filter_delivery_time), $link);
      }

      // Add current filters to URL.
      foreach ($current_filter as $key => $value) {
        // Exclude query arg for current term archive term.
        if ($value === $this->get_current_term_slug()) {
          unset($current_filter[$key]);
        }

        // Exclude self so filter can be unset on click.
        if ($option_is_set && $value === $term->slug) {
          unset($current_filter[$key]);
        }
      }

      if (!empty($current_filter)) {
        asort($current_filter);
        $link = add_query_arg($filter_name, implode(',', $current_filter), $link);

        // Add Query type Arg to URL.
        if ('or' === $query_type && !(1 === count($current_filter) && $option_is_set)) {
          $link = add_query_arg('query_type_' . sanitize_title(str_replace('pa_', '', $taxonomy)), 'or', $link);
        }
        $link = str_replace('%2C', ',', $link);
      }

      if ($count > 0 || $option_is_set) {
        $link = esc_url(apply_filters('woocommerce_layered_nav_link', $link, $term, $taxonomy));
        $term_html = '<a rel="nofollow" href="' . $link . '">' . esc_html($term->name) . '</a>';
      }
      else {
        $link = FALSE;
        $term_html = '<span>' . esc_html($term->name) . '</span>';
      }

      $term_html .= ' ' . apply_filters('woocommerce_layered_nav_count', '<span class="count">(' . absint($count) . ')</span>', $count, $term);

      echo '<li class="woocommerce-widget-layered-nav-list__item wc-layered-nav-term ' . ($option_is_set ? 'woocommerce-widget-layered-nav-list__item--chosen chosen' : '') . '">';
      echo wp_kses_post(apply_filters('woocommerce_layered_nav_term_html', $term_html, $term, $link, $count));
      echo '</li>';
    }

    echo '</ul>';

    return $found;
  }

}
