<?php

namespace Netzstrategen\ShopStandards;

/**
 * Generic plugin lifetime and maintenance functionality.
 */
class Schema {

  /**
   * Registers activation hook callback.
   */
  public static function activate() {
  }

  /**
   * Registers deactivation hook callback.
   */
  public static function deactivate() {
  }

  /**
   * Registers uninstall hook callback.
   */
  public static function uninstall() {
  }

  /**
   * Cron event callback to ensure proper database indexes.
   */
  public static function cron_ensure_back_in_stock() {
    global $wpdb;
    $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts}
      INNER JOIN {$wpdb->postmeta} pm ON {$wpdb->posts}.ID = pm.post_id
      LEFT JOIN {$wpdb->postmeta} moeve ON {$wpdb->posts}.ID = moeve.post_id AND moeve.meta_key LIKE %s
      WHERE pm.meta_key = %s
        AND pm.meta_value <= CURRENT_DATE()
        AND moeve.meta_id IS NULL", [
          '_woocommerce-moeve_id_%',
          '_' . Plugin::PREFIX . '_back_in_stock_date',
    ]));

    foreach ($ids as $id) {
      delete_post_meta($id, '_' . Plugin::PREFIX . '_back_in_stock_date');
    }
  }

}
