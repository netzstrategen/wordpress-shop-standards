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
    $output = Plugin::addFilterToNavLinks($output, 'delivery_time');
    if (empty($output) && isset($_GET['delivery_time'])) {
      ob_start();
      $this->widget_start($args, $instance);
      echo '<ul></ul>';
      $this->widget_end($args);
      $output = ob_get_clean();
    }
    if (isset($_GET['delivery_time'])) {
      $link = remove_query_arg('delivery_time');
      $link = '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . __('Delivery Time', 'woocommerce-german-market') . '</a></li>';
      // Append delivery time filter to active filter list.
      $output = preg_replace('@</ul>@', $link . '</ul>' , $output);
    }
    echo $output;
  }

}
