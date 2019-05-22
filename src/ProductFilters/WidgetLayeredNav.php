<?php

namespace Netzstrategen\ShopStandards\ProductFilters;

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
    ob_start();
    $found = parent::layered_nav_list($terms, $taxonomy, $query_type);
    $output = ob_get_clean();

    $output = DeliveryTime::addFilterToNavLinks($output, 'delivery_time');
    echo $output;
    return $found;
  }

  /**
   * @todo Implement output.
   */
  protected function layered_nav_dropdown($terms, $taxonomy, $query_type) {
    return parent::layered_nav_dropdown($terms, $taxonomy, $query_type);
  }

}
