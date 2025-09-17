<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\ProductFeeds.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Woo feed customized functionality.
 */
class ProductFeeds {

  /**
   * @implements init
   */
  public static function init() {
    // Filters local category path to get full category path.
    add_filter('woo_feed_filter_product_local_category', __CLASS__ . '::woo_feed_filter_product_local_category_callback', 10, 3);
    // Processes webappick product feed file after saving to remove images containing text.
    add_action('ctx_feed_after_save_feed_file', __CLASS__ . '::process_feed_file_after_save', 10, 4);
  }

  /**
   * @implements woo_feed_filter_product_local_category_callback
   */
  public static function woo_feed_filter_product_local_category_callback($product_type, $product, $config) {
    $id = $product->get_id();
    if ($product->is_type('variation')) {
      $id = $product->get_parent_id();
    }
    $full_path = woo_feed_get_terms_list_hierarchical_order($id);
    if (empty($full_path)) {
      $full_path = $product_type;
    }
    return $full_path;
  }

  /**
   * Processes webappick product feed file after saving to remove images containing text.
   *
   * @uses ctx_feed_after_save_feed_file
   */
  public static function process_feed_file_after_save($status, array $feed_info, bool $should_update_last_update_time, bool $auto): void {
    // Only process if the file was saved successfully.
    if (is_wp_error($status) || !$status) {
      return;
    }

    // Only process XML feeds.
    $feed_type_ext = $feed_info['option_value']['feedrules']['feedType'] ?? '';
    if ($feed_type_ext !== 'xml') {
      return;
    }

    // Get the file path from the URL in feed_info.
    $feed_url = $feed_info['option_value']['url'] ?? '';
    if (empty($feed_url)) {
      return;
    }

    // Convert URL to file path.
    $upload_dir = wp_upload_dir();
    $upload_url = $upload_dir['baseurl'];
    $upload_path = $upload_dir['basedir'];

    if (strpos($feed_url, $upload_url) !== 0) {
      return;
    }

    $relative_path = str_replace($upload_url, '', $feed_url);
    $full_file_path = $upload_path . $relative_path;

    // Read the current file content.
    if (!file_exists($full_file_path)) {
      return;
    }

    $contents = file_get_contents($full_file_path);
    if ($contents === false) {
      return;
    }

    // Apply XML-based filtering for additional_image_link nodes.
    $filtered_contents = self::filter_additional_image_links($contents);

    // Only rewrite the file if content changed.
    if ($filtered_contents !== $contents) {
      file_put_contents($full_file_path, $filtered_contents);
    }
  }

  /**
   * Filters XML content to remove additional_image_link nodes containing text using DOMDocument.
   *
   * @param string $contents
   *   The XML content to filter.
   *
   * @return string
   *   The filtered XML content.
   */
  private static function filter_additional_image_links(string $contents): string {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = false;

    // Suppress errors for malformed XML and load content.
    libxml_use_internal_errors(true);

    if (!@$dom->loadXML($contents)) {
      // If XML is malformed, fall back to original content.
      libxml_clear_errors();
      return $contents;
    }

    libxml_clear_errors();

    // Create XPath object to find additional_image_link nodes.
    $xpath = new \DOMXPath($dom);

    // Find all additional_image_link nodes (with or without namespace prefix).
    $image_nodes = $xpath->query("//*[local-name()='additional_image_link']");

    $nodes_to_remove = [];

    foreach ($image_nodes as $node) {
      $image_url = trim($node->textContent);
      if (!empty($image_url)) {
        $attachment_id = attachment_url_to_postid($image_url);
        if ($attachment_id) {
          // Check if the image contains text using our ACF field.
          $contains_text = get_field('contains_text', $attachment_id);
          if ($contains_text) {
            $nodes_to_remove[] = $node;
          }
        }
      }
    }

    // Remove nodes that contain images with text.
    foreach ($nodes_to_remove as $node) {
      if ($node->parentNode) {
        $node->parentNode->removeChild($node);
      }
    }

    // Return the filtered XML.
    return $dom->saveXML();
  }

}
