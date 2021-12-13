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
      add_filter('post_type_link', __CLASS__ . '::get_product_permalink', 10, 2);
    }
    // Set up admin actions to handle product setting.
    add_action('admin_init', __CLASS__ . '::register_product_settings');
    add_action('admin_init', __CLASS__ . '::save_product_settings');
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
    if ($post->post_type !== self::POST_TYPE_PRODUCT) {
      return $post_link;
    }

    $main_term_id   = get_post_meta($post->ID, '_yoast_wpseo_primary_product_cat', TRUE);
    $main_term_slug = get_term($main_term_id, self::TAX_PRODUCT_CAT)->slug ?? NULL;
    if (empty($main_term_slug)) {
      // No primary category found for this product.
      return $post_link;
    }

    $product_cat_placeholder = '%' . self::TAX_PRODUCT_CAT . '%';
    $base_permalink          = self::get_product_base_link($product_cat_placeholder);
    if (empty($base_permalink)) {
      // The product permalink base structure is wrong.
      return $post_link;
    }

    if (self::product_link_has_category($post_link, $product_cat_placeholder, $base_permalink)) {
      // The product link already contains the category.
      return $post_link;
    }

    // Replace the placeholder with main category.
    $product_link = str_replace($product_cat_placeholder, $main_term_slug, $base_permalink);
    $product_link = sprintf('%s/%s', untrailingslashit($product_link), trailingslashit($post->post_name));
    return home_url($product_link);
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
   * Checks whether the current link already contains the main category in it.
   */
  public static function product_link_has_category(string $link, string $product_cat_placeholder, string $base_permalink): bool {
    $matches = [];
    // Replace category placeholder to match the expression.
    $pattern = str_replace($product_cat_placeholder, '[\w-]+', $base_permalink);
    // Complete the pattern including product slug.
    $pattern = sprintf('<%s\/[\w-]+[/]?$>', untrailingslashit($pattern));
    preg_match($pattern, $link, $matches);
    return !empty($matches);
  }

}
