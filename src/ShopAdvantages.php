<?php

namespace Netzstrategen\ShopStandards;

/**
 * Shop and Product Advantages functionality.
 */
class ShopAdvantages {

  /**
   * Init module.
   */
  public static function init(): void {
    if (function_exists('register_field_group') && function_exists('acf_add_options_sub_page')) {
      static::register_acf_options_page();
      static::register_acf_fields();
    }
    add_action('woocommerce_proceed_to_checkout', __CLASS__ . '::display_shop_advantages_on_cart', 30);

    // The Cart block has no equivalent template hook; append the same markup
    // after its "Proceed to Checkout" block via WordPress's native
    // render_block_{$block_name} filter (the block-development-recommended
    // way to inject content next to a specific block, with no JS/build step).
    add_filter('render_block_woocommerce/proceed-to-checkout-block', __CLASS__ . '::appendToBlockProceedToCheckout');
  }

  /**
   * Register ACF options sub-page for advantages.
   */
  public static function register_acf_options_page(): void {
    acf_add_options_sub_page([
      'page_title' => __('Shop Advantages', Plugin::L10N),
      'menu_title' => __('Shop Advantages', Plugin::L10N),
      'parent_slug' => 'woocommerce',
      'menu_slug' => 'shop-advantages',
    ]);
  }

  /**
   * Register ACF field groups for shop and product advantages.
   */
  public static function register_acf_fields(): void {
    register_field_group([
      'key' => 'group_shop_advantages',
      'title' => __('Shop Advantages', Plugin::L10N),
      'fields' => [
        [
          'key' => 'field_shop_advantages',
          'label' => __('Shop Advantages', Plugin::L10N),
          'name' => 'shop_advantages',
          'type' => 'repeater',
          'instructions' => __('List of customer benefits shown in footer/product listing page.', Plugin::L10N),
          'layout' => 'table',
          'button_label' => __('Add advantage', Plugin::L10N),
          'sub_fields' => [
            [
              'key' => 'field_shop_advantage_icon',
              'label' => __('Icon', Plugin::L10N),
              'name' => 'icon',
              'type' => 'image',
              'instructions' => '',
              'mime_types' => 'svg',
              'return_format' => 'url',
              'required' => 1,
            ],
            [
              'key' => 'field_shop_advantage_text',
              'label' => __('Text', Plugin::L10N),
              'name' => 'text',
              'type' => 'text',
              'required' => 1,
              'wrapper' => [
                'width' => '75',
              ],
            ],
          ],
        ],
        [
          'key' => 'field_product_advantages',
          'label' => __('Product Advantages', Plugin::L10N),
          'name' => 'product_advantages',
          'type' => 'repeater',
          'instructions' => __('List of customer benefits shown on product detail page.', Plugin::L10N),
          'layout' => 'table',
          'button_label' => __('Add advantage', Plugin::L10N),
          'sub_fields' => [
            [
              'key' => 'field_product_advantage_icon',
              'label' => __('Icon', Plugin::L10N),
              'name' => 'icon',
              'type' => 'image',
              'instructions' => '',
              'mime_types' => 'svg',
              'return_format' => 'url',
              'required' => 1,
            ],
            [
              'key' => 'field_product_advantage_text',
              'label' => __('Text', Plugin::L10N),
              'name' => 'text',
              'type' => 'text',
              'required' => 1,
              'wrapper' => [
                'width' => '75',
              ],
            ],
          ],
        ],
      ],
      'location' => [[[
        'param' => 'options_page',
        'operator' => '==',
        'value' => 'shop-advantages',
      ]]],
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'active' => 1,
    ]);
  }

  /**
   * Displays shop advantages on the cart page after the checkout buttons.
   *
   * @implements woocommerce_proceed_to_checkout
   */
  public static function display_shop_advantages_on_cart(): void {
    echo static::buildMarkup();
  }

  /**
   * Appends shop advantages after the Cart block's Proceed to Checkout block.
   *
   * @param string $block_content
   *   The block's own rendered HTML.
   *
   * @return string
   *   The block's HTML with the advantages markup appended.
   *
   * @implements render_block_woocommerce/proceed-to-checkout-block
   */
  public static function appendToBlockProceedToCheckout(string $block_content): string {
    return $block_content . static::buildMarkup();
  }

  /**
   * Builds the shop advantages markup from the ACF options page field.
   *
   * @return string
   *   The advantages list markup, or an empty string if none are configured.
   */
  protected static function buildMarkup(): string {
    if (!function_exists('get_field')) {
      return '';
    }

    $shop_advantages = get_field('shop_advantages', 'option');

    if (empty($shop_advantages)) {
      return '';
    }

    $markup = '<div class="shop-advantages-cart">';
    $markup .= '<ul class="shop-advantages">';

    foreach ($shop_advantages as $advantage) {
      if (empty($advantage['icon']) || empty($advantage['text'])) {
        continue;
      }

      $icon_url = is_numeric($advantage['icon']) ? wp_get_attachment_url($advantage['icon']) : $advantage['icon'];

      $markup .= '<li class="shop-advantage">';
      $markup .= '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($advantage['text']) . '" width="20" height="20">';
      $markup .= '<span class="shop-advantage__text">' . wp_kses_post($advantage['text']) . '</span>';
      $markup .= '</li>';
    }

    $markup .= '</ul>';
    $markup .= '</div>';

    return $markup;
  }

}
