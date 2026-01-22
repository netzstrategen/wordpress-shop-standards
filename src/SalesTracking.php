<?php

namespace Netzstrategen\ShopStandards;

/**
 * Tracks 30-day rolling sales count for products and variations.
 *
 * Stores sales data in meta field for feed integration (e.g., moebel.de).
 */
class SalesTracking {

  /**
   * Meta field name for storing 30-day sales count.
   *
   * @var string
   */
  const FIELD_SALES_LAST_30_DAYS = '_sales_count_30_days';

  /**
   * Cron event name for updating sales counts.
   *
   * @var string
   */
  const CRON_EVENT_UPDATE_SALES = Plugin::PREFIX . '/cron/update-sales-last-30-days';

  /**
   * Number of products to process per batch.
   *
   * @var int
   */
  const BATCH_SIZE = 200;

  /**
   * Order statuses to include in sales count.
   *
   * @var array
   */
  const ORDER_STATUSES = ['wc-completed', 'wc-processing'];

  /**
   * Initialize the sales tracking module.
   */
  public static function init(): void {
    add_action(self::CRON_EVENT_UPDATE_SALES, [__CLASS__, 'updateAllProductSales']);

    if (!wp_next_scheduled(self::CRON_EVENT_UPDATE_SALES)) {
      wp_schedule_event(strtotime('04:00:00'), 'daily', self::CRON_EVENT_UPDATE_SALES);
    }
  }

  /**
   * Updates 30-day sales count for all products and variations.
   *
   * This method:
   * 1. Deletes all existing sales count meta
   * 2. Aggregates sales from last 30 days via efficient SQL
   * 3. Bulk-inserts products with their sales counts
   *
   * @param bool $show_progress Whether to show CLI progress (for WP-CLI usage).
   */
  public static function updateAllProductSales(bool $show_progress = false): void {
    global $wpdb;

    // Step 1: Delete all existing sales count meta.
    $wpdb->delete(
      $wpdb->postmeta,
      ['meta_key' => self::FIELD_SALES_LAST_30_DAYS],
      ['%s']
    );

    // Step 2: Aggregate sales from last 30 days.
    $sales_data = self::aggregateSalesLast30Days();

    if (empty($sales_data)) {
      if ($show_progress && class_exists('WP_CLI')) {
        \WP_CLI::log('No sales found in the last 30 days.');
      }
      return;
    }

    // Step 3: Bulk insert products with their sales counts.
    if ($show_progress && class_exists('WP_CLI')) {
      \WP_CLI::log(sprintf('Found %d products with sales in the last 30 days.', count($sales_data)));
      
      // Show all products sorted by sales count
      arsort($sales_data);
      \WP_CLI::log('All products with sales:');
      foreach ($sales_data as $product_id => $qty) {
        $product = wc_get_product($product_id);
        $name = $product ? $product->get_name() : "Product #$product_id";
        \WP_CLI::log("  - $name (ID: $product_id): $qty sales");
      }
      \WP_CLI::log('');
    }

    $chunks = array_chunk($sales_data, self::BATCH_SIZE, true);
    $total_chunks = count($chunks);

    $progress = null;
    if ($show_progress && class_exists('WP_CLI')) {
      $progress = \WP_CLI\Utils\make_progress_bar('Updating sales counts', $total_chunks);
    }

    foreach ($chunks as $chunk) {
      self::bulkInsertSalesMeta($chunk);

      if ($progress) {
        $progress->tick();
      }
    }

    if ($progress) {
      $progress->finish();
    }
  }

  /**
   * Aggregates sales quantities from the last 30 days.
   *
   * Queries WooCommerce order items and aggregates quantities by product ID.
   * Handles both simple products and variations, and also sums variation sales to parent products.
   *
   * @return array Associative array of [product_id => total_quantity].
   */
  private static function aggregateSalesLast30Days(): array {
    global $wpdb;

    $statuses = implode("','", array_map('esc_sql', self::ORDER_STATUSES));
    $date_30_days_ago = gmdate('Y-m-d H:i:s', strtotime('-30 days'));

    // Query aggregates sales for both products and variations using WooCommerce Analytics lookup table.
    // Uses variation_id if > 0 (variation), otherwise product_id (simple product).
    $query = "
      SELECT
        CASE
          WHEN lookup.variation_id > 0 THEN lookup.variation_id
          ELSE lookup.product_id
        END AS product_id,
        lookup.product_id AS parent_id,
        lookup.variation_id,
        SUM(lookup.product_qty) AS total_qty
      FROM {$wpdb->prefix}wc_order_product_lookup AS lookup
      INNER JOIN {$wpdb->posts} AS orders
        ON lookup.order_id = orders.ID
      WHERE orders.post_status IN ('{$statuses}')
        AND lookup.date_created >= %s
      GROUP BY product_id, parent_id, variation_id
      HAVING product_id > 0
    ";

    $results = $wpdb->get_results(
      $wpdb->prepare($query, $date_30_days_ago),
      ARRAY_A
    );

    if (empty($results)) {
      return [];
    }

    $sales_data = [];
    $parent_totals = [];

    foreach ($results as $row) {
      $product_id = (int) $row['product_id'];
      $parent_id = (int) $row['parent_id'];
      $variation_id = (int) $row['variation_id'];
      $quantity = (int) $row['total_qty'];

      // Store sales for the product/variation itself.
      $sales_data[$product_id] = $quantity;

      // If this is a variation, accumulate to parent.
      if ($variation_id > 0 && $parent_id > 0) {
        if (!isset($parent_totals[$parent_id])) {
          $parent_totals[$parent_id] = 0;
        }
        $parent_totals[$parent_id] += $quantity;
      }
    }

    // Add parent totals to sales data.
    foreach ($parent_totals as $parent_id => $total) {
      $sales_data[$parent_id] = $total;
    }

    return $sales_data;
  }

  /**
   * Bulk inserts sales meta for a chunk of products.
   *
   * Uses a single INSERT query for efficiency.
   *
   * @param array $chunk Associative array of [product_id => quantity].
   */
  private static function bulkInsertSalesMeta(array $chunk): void {
    global $wpdb;

    if (empty($chunk)) {
      return;
    }

    $values = [];
    $placeholders = [];

    foreach ($chunk as $product_id => $quantity) {
      $placeholders[] = '(%d, %s, %d)';
      $values[] = $product_id;
      $values[] = self::FIELD_SALES_LAST_30_DAYS;
      $values[] = $quantity;
    }

    $query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(', ', $placeholders);

    $wpdb->query($wpdb->prepare($query, $values));
  }

}
