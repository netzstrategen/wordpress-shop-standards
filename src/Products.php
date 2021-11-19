<?php

namespace Netzstrategen\ShopStandards;

/**
 *
 */
class Products {

  const POST_TYPE_PRODUCT = 'product';

  const TAX_PRODUCT_CAT = 'product_cat';

  /**
   *
   */
  public static function init(): void {
    $obj = new self;
    add_filter('post_type_link', [$obj, 'get_product_permalink'], 10, 2);
  }

  /**
   *
   */
  public function get_product_permalink(string $post_link, \WP_Post $post) {
    if ($post->post_type !== self::POST_TYPE_PRODUCT) {
      return $post_link;
    }
    $main_term_id = get_post_meta($post->ID, '_yoast_wpseo_primary_product_cat', TRUE);
    $main_term_slug = get_term($main_term_id, self::TAX_PRODUCT_CAT)->slug ?? NULL;
    if (empty($main_term_slug)) {
      return $post_link;
    }

    if (strpos($post_link, "/$main_term_slug/") !== FALSE) {
      return $post_link;
    }

    $product_permalink = get_option('woocommerce_permalinks');
    if (empty($product_permalink)) {
      return $post_link;
    }

    $base_permalink = $product_permalink['product_base'];
    $product_cat_placeholder = '%' . self::TAX_PRODUCT_CAT . '%';
    if (strpos($base_permalink, $product_cat_placeholder) === FALSE) {
      return $post_link;
    }

    $product_link = str_replace($product_cat_placeholder, $main_term_slug, $base_permalink);
    $product_link = sprintf('%s/%s', untrailingslashit($product_link), trailingslashit($post->post_name));
    return home_url($product_link);
  }

}
