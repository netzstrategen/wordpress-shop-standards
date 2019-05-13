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

    if ($doc = Plugin::transformFilterLinks($output)) {
      // Append delivery time link to filter list.
      $link = remove_query_arg('delivery_time');
      $link = '<li class="chosen"><a rel="nofollow" aria-label="' . esc_attr__('Remove filter', 'woocommerce') . '" href="' . esc_url($link) . '">' . __('Delivery Time', 'woocommerce-german-market') . '</a></li>';
      $ul = $doc->getElementsByTagName('ul')->item(0);
      $list_item = $doc->createDocumentFragment();
      $list_item->appendXML($link);
      $ul->appendChild($list_item);
      $output = $doc->saveHTML();
    }

    echo $output;
  }

}
