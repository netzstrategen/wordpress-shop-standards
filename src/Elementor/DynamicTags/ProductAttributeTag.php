<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Elementor\DynamicTags\ProductAttributeTag.
 */

namespace Netzstrategen\ShopStandards\Elementor\DynamicTags;

use Netzstrategen\ShopStandards\Plugin;

/**
 * WooCommerce Product Attribute dynamic tag.
 */
class ProductAttributeTag extends \Elementor\Core\DynamicTags\Tag {

  /**
   * Get tag name.
   *
   * @return string
   */
  public function get_name() {
    return 'wc-product-attribute';
  }

  /**
   * Get tag title.
   *
   * @return string
   */
  public function get_title() {
    return __('Product Attribute', Plugin::L10N);
  }

  /**
   * Get tag group.
   *
   * @return string
   */
  public function get_group() {
    return 'woocommerce';
  }

  /**
   * Get tag categories.
   *
   * @return array
   */
  public function get_categories() {
    return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
  }

  /**
   * Register tag controls.
   *
   * @implements _register_controls
   */
  protected function _register_controls() {
    $attributes = [];
    $attributes[''] = __('Select...', Plugin::L10N);

    // Get all global WooCommerce attributes.
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    if ($attribute_taxonomies) {
      foreach ($attribute_taxonomies as $attribute) {
        $attributes['pa_' . $attribute->attribute_name] = $attribute->attribute_label;
      }
    }

    $this->add_control(
      'attribute',
      [
        'label' => __('Attribute', Plugin::L10N),
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => $attributes,
        'default' => '',
      ]
    );

    $this->add_control(
      'output_type',
      [
        'label' => __('Output Type', Plugin::L10N),
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => [
          'plain' => __('Plain Text', Plugin::L10N),
          'link' => __('Clickable Link', Plugin::L10N),
        ],
        'default' => 'plain',
      ]
    );
  }

  /**
   * Renders the tag output.
   *
   * @implements render
   */
  public function render() {
    $attribute = $this->get_settings('attribute');
    $output_type = $this->get_settings('output_type');
    
    if (empty($attribute)) {
      return;
    }

    $product = wc_get_product();
    
    if (!$product) {
      return;
    }

    $value = $product->get_attribute($attribute);
    
    if (empty($value)) {
      return;
    }

    if ($output_type === 'link') {
      // Get attribute terms for the product.
      $terms = wc_get_product_terms($product->get_id(), $attribute, ['fields' => 'all']);
      
      if (!is_wp_error($terms) && !empty($terms)) {
        $links = [];
        foreach ($terms as $term) {
          $term_link = get_term_link($term->term_id, $attribute);
          if (!is_wp_error($term_link)) {
            $links[] = '<a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a>';
          }
          else {
            $links[] = esc_html($term->name);
          }
        }
        echo implode(', ', $links);
      }
      else {
        // Fallback to plain text if terms can't be retrieved.
        echo esc_html($value);
      }
    }
    else {
      echo esc_html($value);
    }
  }

}
