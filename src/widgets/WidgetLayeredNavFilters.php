<?php

namespace Netzstrategen\ShopStandards\Widgets;

/**
 * Widget layered nav filters.
 */
class WidgetLayeredNavFilters extends \WC_Widget_Layered_Nav_Filters {

  /**
   * Output widget.
   *
   * @param array $args
   *   Arguments.
   * @param array $instance
   *   Widget instance.
   *
   * @see WP_Widget
   */
  public function widget($args, $instance) {
    if (!is_shop() && !is_product_taxonomy()) {
      return;
    }

    $_chosen_attributes = \WC_Query::get_layered_nav_chosen_attributes();

    // WPCS: input var ok, CSRF ok.
    $min_price = isset($_GET['min_price']) ? wc_clean(wp_unslash($_GET['min_price'])) : 0;
    // WPCS: input var ok, CSRF ok.
    $max_price = isset($_GET['max_price']) ? wc_clean(wp_unslash($_GET['max_price'])) : 0;
    // WPCS: sanitization ok, input var ok, CSRF ok.
    $rating_filter = isset($_GET['rating_filter']) ? array_filter(array_map('absint', explode(',', wp_unslash($_GET['rating_filter'])))) : [];
    // Retrieves delivery time parameter values.
    $filter_delivery_time = isset($_GET['delivery_time']) ? array_filter(array_map('absint', explode(',', wp_unslash($_GET['delivery_time'])))) : [];
    $base_link = $this->get_current_page_url();

    if (!empty($filter_delivery_time)) {
      $base_link = add_query_arg('delivery_time', implode(',', $filter_delivery_time), $base_link);
    }

    if (0 < count($_chosen_attributes) || 0 < $min_price || 0 < $max_price || !empty($rating_filter) || !empty($filter_delivery_time)) {

      $this->widget_start($args, $instance);

      echo '<ul>';

      // Attributes.
      if (!empty($_chosen_attributes)) {
        foreach ($_chosen_attributes as $taxonomy => $data) {
          foreach ($data['terms'] as $term_slug) {
            $term = get_term_by('slug', $term_slug, $taxonomy);
            if (!$term) {
              continue;
            }

            $filter_name = 'filter_' . sanitize_title(str_replace('pa_', '', $taxonomy));
            // WPCS: input var ok, CSRF ok.
            $current_filter = isset($_GET[$filter_name]) ? explode(',', wc_clean(wp_unslash($_GET[$filter_name]))) : [];
            $current_filter = array_map('sanitize_title', $current_filter);
            $new_filter = array_diff($current_filter, [$term_slug]);

            $link = remove_query_arg(['add-to-cart', $filter_name], $base_link);

            if (count($new_filter) > 0) {
              $link = add_query_arg($filter_name, implode(',', $new_filter), $link);
            }

            echo '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . esc_html($term->name) . '</a></li>';
          }
        }
      }

      if ($min_price) {
        $link = remove_query_arg('min_price', $base_link);
        // WPCS: XSS ok.
        // translators: %s: minimum price.
        echo '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . sprintf(__('Min %s', 'woocommerce'), wc_price($min_price)) . '</a></li>';
      }

      if ($max_price) {
        $link = remove_query_arg('max_price', $base_link);
        // WPCS: XSS ok.
        // translators: %s: maximum price.
        echo '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . sprintf(__('Max %s', 'woocommerce'), wc_price($max_price)) . '</a></li>';
      }

      if (!empty($rating_filter)) {
        foreach ($rating_filter as $rating) {
          $link_ratings = implode(',', array_diff($rating_filter, [$rating]));
          $link = $link_ratings ? add_query_arg('rating_filter', $link_ratings) : remove_query_arg('rating_filter', $base_link);

          // translators: %s: rating.
          echo '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . sprintf(esc_html__('Rated %s out of 5', 'woocommerce'), esc_html($rating)) . '</a></li>';
        }
      }

      // Adds support for woocommerce-german-market delivery time taxonomy terms
      // to be used as product filters.
      if ($filter_delivery_time) {
        $link = remove_query_arg('delivery_time', $base_link);
        echo '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . __('Delivery Time', 'woocommerce-german-market') . '</a></li>';
      }

      echo '</ul>';

      $this->widget_end($args);
    }
  }

}
