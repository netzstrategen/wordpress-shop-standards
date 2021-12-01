<?php

namespace Netzstrategen\ShopStandards;

/**
 * Meant to handle special Product features.
 */
class Products {

  const POST_TYPE_PRODUCT = 'product';

  const TAX_PRODUCT_CAT = 'product_cat';

  const FIELD_ENFORCE_CAT_LINKS = Plugin::PREFIX . '_enforce_main_category_links';

  /**
   * Stores the product base link.
   *
   * @var string
   */
  private string $product_base_link;

  /**
   * Initialize the product module.
   */
  public static function init(): void {
    $obj = new self();
    if (get_option(self::FIELD_ENFORCE_CAT_LINKS)) {
      add_filter('post_type_link', [$obj, 'get_product_permalink'], 10, 2);
    }
    add_action('admin_init', [$obj, 'register_product_settings']);
    add_action('admin_init', [$obj, 'save_product_settings']);
  }

  /**
   * Registers custom product settings.
   */
  public function register_product_settings(): void {
    add_settings_field(self::FIELD_ENFORCE_CAT_LINKS,
      __('Enforce main category on product links', Plugin::L10N),
      [$this, 'render_permalink_option'],
      'permalink',
      'woocommerce-permalink');
  }

  /**
   * Persists custom product settings.
   */
  public function save_product_settings(): void {
    if ($this->is_saving_settings()) {
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
  private function is_saving_settings(): bool {
    return !empty($_POST) && strpos($_SERVER['REQUEST_URI'],
        'options-permalink') !== FALSE;
  }

  /**
   * Renders custom option in page.
   */
  public function render_permalink_option() { ?>
      <input name="<?= self::FIELD_ENFORCE_CAT_LINKS ?>" type="checkbox"
             value="1" <?php
              checked(get_option(self::FIELD_ENFORCE_CAT_LINKS)) ?> />
    <?php
  }

  /**
   * Ensures the product link contains the main category.
   */
  public function get_product_permalink(
    string $post_link,
    \WP_Post $post
  ): string {
    if ($post->post_type !== self::POST_TYPE_PRODUCT) {
      return $post_link;
    }

    $main_term_id   = get_post_meta($post->ID,
      '_yoast_wpseo_primary_product_cat',
      TRUE);
    $main_term_slug = get_term($main_term_id,
        self::TAX_PRODUCT_CAT)->slug ?? NULL;
    if (empty($main_term_slug)) {
      return $post_link;
    }

    if (strpos($post_link, "/$main_term_slug/") !== FALSE) {
      return $post_link;
    }

    $product_cat_placeholder = '%' . self::TAX_PRODUCT_CAT . '%';
    $base_permalink          = $this->get_product_base_link($product_cat_placeholder);
    if (empty($base_permalink)) {
      return $post_link;
    }

    $product_link = str_replace($product_cat_placeholder,
      $main_term_slug,
      $base_permalink);
    $product_link = sprintf('%s/%s',
      untrailingslashit($product_link),
      trailingslashit($post->post_name));
    return home_url($product_link);
  }

  /**
   * Retrieves the current prodyct base link to be replaced.
   */
  public function get_product_base_link(string $cat_placeholder): string {
    if (isset($this->product_base_link)) {
      return $this->product_base_link;
    }

    $product_permalink = get_option('woocommerce_permalinks');
    if (empty($product_permalink)) {
      return '';
    }

    $base_permalink = $product_permalink['product_base'];
    if (strpos($base_permalink, $cat_placeholder) !== FALSE) {
      $this->product_base_link = $base_permalink;
    }
    return $this->product_base_link ?? '';
  }

}
