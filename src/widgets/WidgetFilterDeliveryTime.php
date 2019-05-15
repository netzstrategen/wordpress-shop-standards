<?php

namespace Netzstrategen\ShopStandards\Widgets;

use Netzstrategen\ShopStandards\Plugin;

/**
 * Widget layered nav class.
 */
class WidgetFilterDeliveryTime extends \WC_Widget {
  /**
   * Widget name.
   *
   * @var string
   */
  const WIDGET_NAME = Plugin::L10N . '_widget_filter_delivery_time';

  /**
   * Constructor.
   */
  public function __construct() {
    $this->widget_cssclass = 'woocommerce widget_layered_nav woocommerce-widget-layered-nav';
    $this->widget_id = static::WIDGET_NAME;
    $this->widget_name = __('Filter Products by Delivery Time', Plugin::L10N);
    $this->widget_description = __('Display a list of delivery times to filter products in your store.', Plugin::L10N);

    parent::__construct();

    add_filter('get_terms_orderby', __CLASS__ . '::get_terms_orderby', 10, 3);
  }

  /**
   * Sanitizes widget form values as they are saved.
   */
  public function update($new_instance, $old_instance) {
    $this->initSettings();
    return parent::update($new_instance, $old_instance);
  }

  /**
   * Outputs the administrative widget form.
   */
  public function form($instance) {
    $this->initSettings();
    parent::form($instance);
  }

  /**
   * Front-end display of widget.
   */
  public function widget($args, $instance) {
    if (!is_shop() && !is_product_taxonomy()) {
      return;
    }

    if (!$delivery_times = static::getProductsDeliveryTimes()) {
      return;
    }

    $filter_delivery_time = isset($_GET['delivery_time']) ? intval($_GET['delivery_time']) : 0;

    ob_start();

    $this->widget_start($args, array_merge($instance, ['title' => __('Delivery Time:', 'woocommerce-german-market')]));

    $item_class = 'woocommerce-widget-layered-nav-list__item wc-layered-nav-term';
    $item_chosen_class = 'woocommerce-widget-layered-nav-list__item--chosen chosen';

    echo '<ul class="product_delivery_time_widget">';
    foreach ($delivery_times as $delivery_time) {
      if ($instance['delivery_time-' . $delivery_time->term_id]) {
        echo sprintf('<li class="%s">', $delivery_time->term_id === $filter_delivery_time ? implode(' ', [$item_class, $item_chosen_class]) : $item_class);
        echo sprintf('<a rel="nofollow" href="%s">%s</a>', add_query_arg('delivery_time', $delivery_time->term_id), $delivery_time->name);
      }
    }
    echo '</ul>';
    $this->widget_end($args);

    echo ob_get_clean();
  }

  /**
   * Inits widget controls settings.
   */
  public function initSettings() {
    if (!$delivery_times = static::getProductsDeliveryTimes()) {
      return;
    }

    // Builds a list of checkboxes with woocommerce-german-market delivery time
    // taxonomy terms to be used as product filters.
    foreach ($delivery_times as $delivery_time) {
      $this->settings['delivery_time-' . $delivery_time->term_id] = [
        'type' => 'checkbox',
        'std' => 0,
        'label' => $delivery_time->name,
      ];
    }
  }

  /**
   * Returns the existing delivery time taxonomy terms.
   *
   * @param array $args
   *   Arguments for the query.
   *
   * @return array
   *   Products delivery times.
   */
  public static function getProductsDeliveryTimes(array $args = []) {
    $query_args = wp_parse_args($args, [
      'taxonomy' => 'product_delivery_times',
      'hide_empty' => FALSE,
      'orderby' => 'slug',
      'order' => 'ASC',
    ]);
    return get_terms($query_args);
  }

  /**
   * Typecasts slug to integer to achieve proper numeric sorting.
   *
   * @implements get_terms_orderby
   *
   * @return string
   */
  public static function get_terms_orderby(string $orderby, array $query_vars, array $taxonomy): string {
    if (in_array('product_delivery_times', $taxonomy, TRUE) && $query_vars['orderby'] === 'slug') {
      $orderby = $orderby . '+0';
    }
    return $orderby;
  }

}
