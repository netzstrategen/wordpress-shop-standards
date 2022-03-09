<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Seo.
 */

namespace Netzstrategen\ShopStandards;

/**
 * SEO related settings and actions.
 */
class Seo {

  public static function init() {
    add_filter('woocommerce_get_settings_shop_standards', __CLASS__ . '::woocommerce_get_settings_shop_standards');

    if (is_admin()) {
      return;
    }

    // Blocks search indexing on search pages.
    add_action('wp_head', __CLASS__ . '::wp_head');
    // Disables Yoast adjacent links.
    add_filter('wpseo_disable_adjacent_rel_links', __CLASS__ . '::wpseo_disable_adjacent_rel_links');

    // To avoid all no-follow links introduced by the products filters sidebar
    // widgets, we convert them into span tags.
    // Removes link tags from list of products filters.
    add_filter('woocommerce_layered_nav_term_html', __CLASS__ . '::woocommerce_layered_nav_term_html', 999, 4);
    // Starts capturing the sidebar content.
    add_action('dynamic_sidebar_before', __CLASS__ . '::dynamic_sidebar_before');
    // Removes link tags from selected products filters tags.
    add_action('dynamic_sidebar_after', __CLASS__ . '::dynamic_sidebar_after');
  }

  /**
   * Adds checkout specific settings.
   *
   * @implements woocommerce_get_settings_shop_standards
   */
  public static function woocommerce_get_settings_shop_standards(array $settings): array {
    $settings[] = [
      'type' => 'title',
      'name' => __('SEO settings', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_robots_noindex_secondary_product_listings',
      'name' => __('Index first page of paginated products listings only', Plugin::L10N),
      'desc_tip' => __('If checked, noindex meta tag will be added to paginated products listing pages, starting from the second page.', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'checkbox',
      'id' => '_wpseo_disable_adjacent_rel_links',
      'name' => __('Disable Yoast SEO adjacent navigation links.', Plugin::L10N),
      'desc_tip' => __('Avoids unwanted rankings of search result URLs as well as paginated listing pages in case the shop\'s product listing is using infinite scrolling or lazy loading to display further products without pagination links.', Plugin::L10N),
    ];
    $settings[] = [
      'type' => 'sectionend',
      'id' => Plugin::L10N,
    ];
    return $settings;
  }

  /**
   * Removes link tags from list of products filters.
   *
   * @implements wp_head
   */
  public static function wp_head() {
    $noindex_second_page = get_option(Plugin::L10N . '_robots_noindex_secondary_product_listings');

    if (is_search() || (is_paged() && $noindex_second_page)) {
      echo '<meta name="robots" content="noindex">';
    }
  }

  /**
   * Disables Yoast adjacent links.
   *
   * @implements wpseo_disable_adjacent_rel_links
   */
  public static function wpseo_disable_adjacent_rel_links(): bool {
    return get_option(Plugin::L10N . '_wpseo_disable_adjacent_rel_links');
  }

  /**
   * Removes link tags from list of products filters.
   *
   * @implements woocommerce_layered_nav_term_html
   */
  public static function woocommerce_layered_nav_term_html($term_html, $term, $link, $count) {
    if (strpos($term_html, '<a') !== 0) {
      return $term_html;
    }
    return sprintf('<span class="product-filter-term" data-url="%s">%s</span> <span class="count">(%d)</span>', $link, esc_html($term->name), $count);
  }

  /**
   * Starts capturing the sidebar content.
   *
   * @implements dynamic_sidebar_before
   */
  public static function dynamic_sidebar_before() {
    ob_start();
  }

  /**
   * Removes link tags from selected products filters tags.
   *
   * @implements dynamic_sidebar_after
   */
  public static function dynamic_sidebar_after() {
    $sidebar_content = ob_get_clean();
    $new_sidebar_content = preg_replace('@(<li\s+class="chosen.*)<a(.*)href="(.*)">(.*)</a></li>@', '$1<span data-url="$3">$4</a></li>', $sidebar_content);
    echo $new_sidebar_content ?: $sidebar_content;
  }

  /**
   * Adds product GTIN to schema.org structured data.
   *
   * @implements woocommerce_structured_data_product
   */
  public static function getProductGtin($data) {
    global $product;

    if (!$gtin = get_post_meta($product->get_id(), '_' . Plugin::PREFIX . '_gtin', TRUE)) {
      return $data;
    }

    switch (strlen(trim($gtin))) {
      case 8:
        $gtin_format_type = 'gtin8';
        break;

      case 13:
        $gtin_format_type = 'gtin13';
        break;

      case 14:
        $gtin_format_type = 'gtin14';
        break;

      default:
        $gtin_format_type = 'gtin12';
    }

    $data[$gtin_format_type] = $gtin;
    return $data;
  }

  /**
   * Adds product variation price to schema.org.
   *
   * @implements woocommerce_structured_data_product_offer
   */
  public static function getProductVariationPrice($markup, $product) {
    if ($product->get_type() !== 'variable') {
      return $markup;
    }

    // Identify the variation that matches the attributes in the URL.
    $attributes = WooCommerce::getVariationAttributesFromUrl();
    if (
      $attributes &&
      $variationId = WooCommerce::getVariationIdByAttributes(
        $product->get_id(),
        $attributes
      )
    ) {
      $variation = wc_get_product($variationId);
      if ($variation) {
        $price = wc_prices_include_tax() ?
          wc_get_price_including_tax($variation) :
          wc_get_price_excluding_tax($variation);
        $markup['price'] = $price;
      }
    };

    return $markup;
  }

  /**
   * Fixes schema.org prices according to tax settings.
   *
   * When retrieving product prices to add into schema.org woocommerce is not
   * considering if they already includes taxes, as set in the backend. This
   * seems to be caused by aelia-currency-switcher currency conversion.
   *
   * See https://bit.ly/2ZLZxIs
   *
   * @implements woocommerce_structured_data_product_offer
   */
  public static function adjustPrice($markup, $product) {
    if (!class_exists('\WC_Aelia_CurrencyPrices_Manager')) {
      return $markup;
    }

    $prices_include_tax = wc_prices_include_tax();

    if ($product->get_type() === 'variable') {
      $lowest = $product->get_variation_price('min', $prices_include_tax);
      $highest = $product->get_variation_price('max', $prices_include_tax);
      if ($lowest === $highest) {
        $markup['price'] = wc_format_decimal($lowest, wc_get_price_decimals());
        $markup['priceSpecification']['price'] = wc_format_decimal(
          $lowest,
          wc_get_price_decimals()
        );
      }
      else {
        $markup['lowPrice'] = wc_format_decimal(
          $lowest, wc_get_price_decimals()
        );
        $markup['highPrice'] = wc_format_decimal(
          $highest, wc_get_price_decimals()
        );
      }
    }
    else {
      $product_price = $prices_include_tax ?
        wc_get_price_including_tax($product) :
        wc_get_price_excluding_tax($product);

      $markup['price'] = $product_price;
      $markup['priceSpecification']['price'] = $product_price;
    }

    return $markup;
  }

  /**
   * Fixes schema.org product availability.
   *
   * @implements woocommerce_structured_data_product_offer
   */
  public static function adjustAvailability($markup, $product) {
    if ($product->get_type() === 'variable') {
      $in_stock = (bool) count(array_filter($product->get_available_variations('object'), function ($variant) {
        return ($variant->get_stock_quantity() > 0) || $variant->backorders_allowed();
      }));
    }
    if (($product->get_stock_quantity() > 0) || $product->backorders_allowed() || !empty($in_stock)) {
      $markup['availability'] = 'https://schema.org/InStock';
    }
    return $markup;
  }

}
