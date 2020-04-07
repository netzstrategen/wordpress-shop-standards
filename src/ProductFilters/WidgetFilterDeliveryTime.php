<?php

namespace Netzstrategen\ShopStandards\ProductFilters;

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
   * List of delivery time values.
   *
   * @var array
   */
  public static $delivery_times = [];

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

    ob_start();

    $this->widget_start($args, array_merge($instance, ['title' => __('Delivery Time:', 'woocommerce-german-market')]));

    $item_class = 'woocommerce-widget-layered-nav-list__item wc-layered-nav-term';
    $item_chosen_class = 'woocommerce-widget-layered-nav-list__item--chosen chosen';

    if ($filter_values = $_GET['delivery_time'] ?? []) {
      $filter_values = array_filter(array_map('absint', explode(',', wp_unslash($filter_values))));
    }

    echo '<ul class="product_delivery_time_widget">';
    foreach ($delivery_times as $delivery_time) {
      if (empty($instance['delivery_time-' . $delivery_time->term_id])) {
        continue;
      }
      $values = $filter_values;
      // Add delivery time to filter array if not active already, remove otherwise.
      if (!in_array($delivery_time->term_id, $values, TRUE)) {
        $values[] = $delivery_time->term_id;
      }
      else {
        $values = array_diff($values, [$delivery_time->term_id]);
      }
      // Add filter values as query argument or remove parameter if empty.
      // If the current value is selected the value will not be added.
      if ($values) {
        $link = add_query_arg('delivery_time', implode(',', $values));
      }
      else {
        $link = remove_query_arg('delivery_time');
      }
      $chosen = in_array($delivery_time->term_id, $filter_values, TRUE);
      echo sprintf('<li class="%s">', $chosen ? $item_class . ' ' . $item_chosen_class : $item_class);
      echo sprintf('<a rel="nofollow" href="%s">%s</a></li>', $link, $delivery_time->name);
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
    if (static::$delivery_times) {
      return static::$delivery_times;
    }
    $query_args = wp_parse_args($args, [
      'taxonomy' => 'product_delivery_times',
      'hide_empty' => FALSE,
      'orderby' => 'slug',
      'order' => 'ASC',
    ]);
    $terms = get_terms($query_args);
    static::$delivery_times = $terms;
    return $terms;
  }

  /**
   * Typecasts slug to integer to achieve proper numeric sorting.
   *
   * @implements get_terms_orderby
   */
  public static function get_terms_orderby(string $orderby, array $query_vars, ?array $taxonomy = []): string {
    if ($taxonomy && in_array('product_delivery_times', $taxonomy, TRUE) && $query_vars['orderby'] === 'slug') {
      $orderby = $orderby . '+0';
    }
    return $orderby;
  }

}
