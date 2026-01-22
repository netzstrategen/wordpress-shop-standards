<?php

namespace Netzstrategen\ShopStandards;

/**
 * Admin page for viewing 30-day sales tracking data.
 */
class SalesTrackingAdmin {

  /**
   * Initialize admin page hooks.
   */
  public static function init(): void {
    add_action('admin_menu', [__CLASS__, 'addAdminMenu']);
  }

  /**
   * Adds admin menu page.
   */
  public static function addAdminMenu(): void {
    add_submenu_page(
      'woocommerce',
      __('30-Day Sales Tracking', Plugin::L10N),
      __('Sales Tracking', Plugin::L10N),
      'manage_woocommerce',
      'shop-standards-sales-tracking',
      [__CLASS__, 'renderAdminPage']
    );
  }

  /**
   * Renders the admin page.
   */
  public static function renderAdminPage(): void {
    // Get all products and variations with sales count meta
    $args = [
      'post_type' => ['product', 'product_variation'],
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'meta_query' => [
        [
          'key' => SalesTracking::FIELD_SALES_LAST_30_DAYS,
          'compare' => 'EXISTS',
        ],
      ],
      'orderby' => 'meta_value_num',
      'order' => 'DESC',
      'meta_key' => SalesTracking::FIELD_SALES_LAST_30_DAYS,
    ];

    $query = new \WP_Query($args);

    // Build data structures in a single pass
    $products = [];
    $variations_by_parent = [];
    $product_ids = [];
    $total_sales = 0;

    foreach ($query->posts as $post) {
      $product_id = $post->ID;
      $sales_count = (int) get_post_meta($product_id, SalesTracking::FIELD_SALES_LAST_30_DAYS, true);
      $total_sales += $sales_count;

      if ($post->post_type === 'product_variation' && $post->post_parent > 0) {
        $variations_by_parent[$post->post_parent][] = [
          'id' => $product_id,
          'title' => $post->post_title,
          'sales' => $sales_count,
        ];
      } else {
        $product_ids[] = $product_id;
        $products[$product_id] = [
          'title' => $post->post_title,
          'sales' => $sales_count,
        ];
      }
    }

    // Batch load all product types in a single query
    $product_types = [];
    if (!empty($product_ids)) {
      $terms = wp_get_object_terms($product_ids, 'product_type', ['fields' => 'all_with_object_id']);
      if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
          $product_types[$term->object_id] = $term->name;
        }
      }
    }

    $total_products = count($query->posts);

    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('30-Day Sales Tracking', Plugin::L10N); ?></h1>
      
      <div class="notice notice-info inline">
        <p>
          <strong><?php echo esc_html($total_products); ?></strong> products with sales | 
          <strong><?php echo esc_html($total_sales); ?></strong> total units sold in last 30 days
        </p>
      </div>

      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th style="width: 80px;"><?php esc_html_e('Type', Plugin::L10N); ?></th>
            <th><?php esc_html_e('Product Name', Plugin::L10N); ?></th>
            <th style="width: 100px;"><?php esc_html_e('Product ID', Plugin::L10N); ?></th>
            <th style="width: 120px;"><?php esc_html_e('30-Day Sales', Plugin::L10N); ?></th>
            <th style="width: 100px;"><?php esc_html_e('Actions', Plugin::L10N); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($query->posts)): ?>
            <tr>
              <td colspan="5" style="text-align: center;">
                <?php esc_html_e('No sales data found. Run the update command first.', Plugin::L10N); ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $product_id => $product_data): ?>
              <?php
                $product_type = $product_types[$product_id] ?? 'simple';
                $has_variations = isset($variations_by_parent[$product_id]);
                
                $type_label = match($product_type) {
                  'simple' => '📦 Simple',
                  'variable' => '📂 Variable',
                  'grouped' => '📚 Grouped',
                  'external' => '🔗 External',
                  default => '📦 Product',
                };
              ?>
              <tr class="product-parent product-type-<?php echo esc_attr($product_type); ?>">
                <td>
                  <span title="<?php echo esc_attr(ucfirst($product_type) . ' Product'); ?>">
                    <?php echo esc_html($type_label); ?>
                  </span>
                </td>
                <td>
                  <strong><?php echo esc_html($product_data['title']); ?></strong>
                  <?php if ($has_variations): ?>
                    <span style="color: #666; font-size: 0.9em;">
                      (<?php echo count($variations_by_parent[$product_id]); ?> variations)
                    </span>
                  <?php endif; ?>
                </td>
                <td><?php echo esc_html($product_id); ?></td>
                <td><strong><?php echo esc_html($product_data['sales']); ?></strong></td>
                <td>
                  <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>" class="button button-small">
                    <?php esc_html_e('Edit', Plugin::L10N); ?>
                  </a>
                </td>
              </tr>

              <?php if ($has_variations): ?>
                <?php foreach ($variations_by_parent[$product_id] as $variation): ?>
                  <tr class="product-variation">
                    <td style="padding-left: 30px;">
                      <span title="<?php esc_attr_e('Variation', Plugin::L10N); ?>">
                        └ 🔹
                      </span>
                    </td>
                    <td style="padding-left: 20px;">
                      <?php echo esc_html($variation['title']); ?>
                    </td>
                    <td><?php echo esc_html($variation['id']); ?></td>
                    <td><?php echo esc_html($variation['sales']); ?></td>
                    <td>
                      <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>" class="button button-small">
                        <?php esc_html_e('Edit', Plugin::L10N); ?>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <style>
        .product-variation td {
          background-color: #f9f9f9;
          font-size: 0.95em;
        }
        .product-parent td {
          background-color: #fff;
        }
        .product-type-variable td {
          font-weight: 600;
        }
        .product-type-simple td {
          font-weight: 500;
        }
      </style>
    </div>
    <?php
  }

}
