<?php

namespace Netzstrategen\ShopStandards\Widgets;

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
    if (empty($output) && isset($_GET['delivery_time'])) {
      ob_start();
      $this->widget_start($args, $instance);
      echo '<ul></ul>';
      $this->widget_end($args);
      $output = ob_get_clean();
    }
    $output = Plugin::addFilterToNavLinks($output, 'delivery_time');
    if (!$filter_values = $_GET['delivery_time'] ?? []) {
      echo $output;
      return;
    }
    $filter_values = array_filter(array_map('absint', explode(',', wp_unslash($filter_values))));
    $delivery_times = WidgetFilterDeliveryTime::getProductsDeliveryTimes();
    $delivery_times = wp_list_pluck($delivery_times, 'name', 'term_id');
    $links = [];
    foreach ($filter_values as $filter_value) {
      if ($values = array_diff($filter_values, [$filter_value])) {
        $link = add_query_arg('delivery_time', implode(',', $values));
      }
      else {
        $link = remove_query_arg('delivery_time');
      }
      $name = $delivery_times[$filter_value];
      $links[] = '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . $name . '</a></li>';
    }
    // Append delivery time filter to active filter list.
    $output = preg_replace('@</ul>@', implode("\n", $links) . '</ul>' , $output);
    echo $output;
  }

}
