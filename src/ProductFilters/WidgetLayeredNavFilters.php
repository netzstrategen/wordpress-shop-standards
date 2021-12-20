<?php

namespace Netzstrategen\ShopStandards\ProductFilters;

use Netzstrategen\ShopStandards\Plugin;

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
    ob_start();
    parent::widget($args, $instance);
    $output = ob_get_clean();
    // Return the original widget output when no delivery time is present.
    if (!isset($_GET[DeliveryTime::DELIVERY_TIME_VAR])) {
      echo $output;
      return;
    }
    // When only the delivery time filter is applied,
    // but not handled by the parent widget, prepares the structure.
    if (empty($output)) {
      ob_start();
      $this->widget_start($args, $instance);
      echo '<ul></ul>';
      $this->widget_end($args);
      $output = ob_get_clean();
    }
    $output = DeliveryTime::addFilterToNavLinks($output, DeliveryTime::DELIVERY_TIME_VAR);
    $filter_values = array_filter(array_map('absint', explode(',', wp_unslash($_GET[DeliveryTime::DELIVERY_TIME_VAR]))));
    $delivery_times = WidgetFilterDeliveryTime::getProductsDeliveryTimes();
    $delivery_times = wp_list_pluck($delivery_times, 'name', 'term_id');
    $links = [];
    foreach ($filter_values as $filter_value) {
      // Ensures the applied time filter exists and is valid.
      if ($name = $delivery_times[$filter_value] ?? FALSE) {
        if ($values = array_diff($filter_values, [$filter_value])) {
          $link = add_query_arg(DeliveryTime::DELIVERY_TIME_VAR, implode(',', $values));
        }
        else {
          $link = remove_query_arg(DeliveryTime::DELIVERY_TIME_VAR);
        }
        $links[] = '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . $name . '</a></li>';
      }
    }
    // Append delivery time filter to active filter list.
    $output = preg_replace('@</ul>@', implode("\n", $links) . '</ul>', $output);
    echo $output;
  }

}
