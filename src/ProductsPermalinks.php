<?php

namespace Netzstrategen\ShopStandards;

/**
 * Meant to handle Product permalinks features.
 */
class ProductsPermalinks {

  const POST_TYPE_PRODUCT = 'product';

  const TAX_PRODUCT_CAT = 'product_cat';

  const FIELD_ENFORCE_CAT_LINKS = Plugin::PREFIX . '_enforce_main_category_links';

  /**
   * Initialize the product module.
   */
  public static function init(): void {
    // Set up the product link filter only when the setting is active.
    if (get_option(self::FIELD_ENFORCE_CAT_LINKS)) {
      add_filter('post_type_link', __CLASS__ . '::get_product_permalink', PHP_INT_MAX, 2);
      add_filter('wpseo_canonical', __CLASS__ . '::match_canonical_url', PHP_INT_MAX);
      add_action('template_redirect', __CLASS__ . '::redirect_to_product_permalink', 4);
    }
    // Set up admin actions to handle product setting.
    add_action('admin_init', __CLASS__ . '::register_product_settings');
    add_action('admin_init', __CLASS__ . '::save_product_settings');
  }

  /**
   * Ensures the canonical url matches the same product link fixed.
   */
  public static function match_canonical_url(?string $canonical): ?string {
    if ($canonical && is_product()) {
      // Executes the same flow to get the right product link.
      $canonical = get_the_permalink();
    }
    return $canonical;
  }

  /**
   * Redirects product requests to the enforced product permalink.
   *
   * WooCommerce's product canonical redirect compares the requested category
   * against the full category hierarchy, while this plugin intentionally emits
   * product permalinks with only the main category slug. Remove WooCommerce's
   * redirect to avoid self-redirect loops, then compare against the filtered
   * permalink instead.
   */
  public static function redirect_to_product_permalink(): void {
    if (!is_product()) {
      return;
    }

    remove_action('template_redirect', 'wc_product_canonical_redirect', 5);

    if (!function_exists('wc_get_product')) {
      return;
    }

    $product = wc_get_product(get_the_ID());
    if (!$product) {
      return;
    }

    $request_path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!$request_path) {
      return;
    }

    $current_url = trailingslashit(home_url($request_path));
    $product_url = trailingslashit($product->get_permalink());
    if ($current_url === $product_url) {
      return;
    }

    $query_vars = isset($_GET) && is_array($_GET) ? $_GET : [];
    wp_safe_redirect(add_query_arg($query_vars, $product_url), 301);
    exit;
  }

  /**
   * Registers custom product settings.
   */
  public static function register_product_settings(): void {
    add_settings_field(self::FIELD_ENFORCE_CAT_LINKS,
      __('Enforce main category on product links', Plugin::L10N),
      __CLASS__ . '::render_permalink_option',
      'permalink',
      'woocommerce-permalink');
  }

  /**
   * Persists custom product settings.
   */
  public static function save_product_settings(): void {
    if (self::is_saving_settings()) {
      $option_checked = $_POST[self::FIELD_ENFORCE_CAT_LINKS] ?? 0;
      update_option(self::FIELD_ENFORCE_CAT_LINKS, $option_checked);
    }
  }

  /**
   * Determines if the custom settings should be stored.
   *
   * @return bool
   *   TRUE if the current screen is correct.
   */
  private static function is_saving_settings(): bool {
    return !empty($_POST) && strpos($_SERVER['REQUEST_URI'], 'options-permalink') !== FALSE;
  }

  /**
   * Renders custom option in page.
   */
  public static function render_permalink_option(): void { ?>
      <input name="<?php echo self::FIELD_ENFORCE_CAT_LINKS; ?>" type="checkbox" value="1" <?php checked(get_option(self::FIELD_ENFORCE_CAT_LINKS)); ?> />
    <?php
  }

  /**
   * Ensures the product link contains the main category.
   */
  public static function get_product_permalink(string $post_link, \WP_Post $post): string {
    if ($post->post_type !== self::POST_TYPE_PRODUCT || $post->post_status !== 'publish') {
      return $post_link;
    }

    $main_term_id   = get_post_meta($post->ID, '_yoast_wpseo_primary_product_cat', TRUE);
    $main_term_slug = get_term($main_term_id, self::TAX_PRODUCT_CAT)->slug ?? NULL;
    if (empty($main_term_slug)) {
      $main_term_slug = self::get_main_term_from_post($post);
      if (empty($main_term_slug)) {
        // No primary category found for this product.
        return $post_link;
      }
    }

    $product_cat_placeholder = '%' . self::TAX_PRODUCT_CAT . '%';
    $base_permalink          = self::get_product_base_link($product_cat_placeholder);
    if (empty($base_permalink)) {
      // The product permalink base structure is wrong.
      return $post_link;
    }

    // Replace the placeholder with main category.
    $product_link = str_replace($product_cat_placeholder, $main_term_slug, $base_permalink);
    $product_link = sprintf('%s/%s', untrailingslashit($product_link), trailingslashit($post->post_name));
    /**
     * Returns the product permalink with the option to filter it.
     *
     * @param string $product_link The product permalink.
     * @return string The filtered product permalink.
     */
    return apply_filters('shop_standards_get_product_permalink', home_url($product_link));
  }

  /**
   * Retrieves the current product base link to be replaced.
   */
  public static function get_product_base_link(string $cat_placeholder): string {
    $product_permalink = get_option('woocommerce_permalinks');
    if (empty($product_permalink)) {
      return '';
    }

    $base_permalink = $product_permalink['product_base'];
    return strpos($base_permalink, $cat_placeholder) !== FALSE ? $base_permalink : '';
  }

  /**
   * Determines the main taxonomy term based on the nesting level from them.
   */
  public static function get_main_term_from_post(\WP_Post $post): string {
    $terms = get_the_terms($post, self::TAX_PRODUCT_CAT);
    if (!$terms || !is_array($terms)) {
      return '';
    }

    $term_hierarchy = [];
    foreach ($terms as $term) {
      $level = 0;
      $slug = $term->slug;
      while ($term->parent) {
        $level++;
        $term = get_term($term->parent, self::TAX_PRODUCT_CAT);
      }
      $term_hierarchy[] = ['level' => $level, 'slug' => $slug];
    }
    // Sort the terms using the nesting level as priority.
    usort($term_hierarchy, fn($a, $b) => $b['level'] <=> $a['level']);
    return current($term_hierarchy)['slug'];
  }

}
