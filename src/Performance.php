<?php

namespace Netzstrategen\ShopStandards;

/**
 * Performance improvements.
 */
class Performance {

  /**
   * WooCommerce scripts to be defer or async loaded.
   *
   * @var array
   */
  const SCRIPTS_ASYNC_LOAD = [
    'jquery-blockui' => 'defer',
    'wc-add-to-cart' => 'defer',
    'js-cookie' => 'defer',
    'woocommerce' => 'defer',
    'wc-cart-fragments' => 'defer',
    'selectWoo' => 'async',
    'select2' => 'async',
    'wc-country-select' => 'defer',
  ];

  /**
   * Styles to be defer or async loaded.
   *
   * @var array
   */
  const STYLES_ASYNC_LOAD = [];

  /**
   * Scripts to be dequeued.
   *
   * @var array
   */
  const SCRIPTS_DEQUEUE = [];

  /**
   * Styles to be dequeued.
   *
   * @var array
   */
  const STYLES_DEQUEUE = [];

  /**
   * URLs of resources to prefetch.
   *
   * @var array
   */
  const RESOURCES_URL_PREFETCH = [];

  /**
   * Dequeue unwanted scripts and styles.
   *
   * @implements wp_enqueue_scripts
   */
  public static function wp_enqueue_scripts() {
    $dequeue_scripts = static::getDequeueScripts();
    foreach ($dequeue_scripts as $handle) {
      wp_dequeue_script($handle);
    }
    $dequeue_styles = static::getDequeueStyles();
    foreach ($dequeue_styles as $handle) {
      wp_dequeue_style($handle);
    }
  }

  /**
   * Loads styles asynchronously.
   *
   * @implements wp_head
   */
  public static function wp_head() {
    static::preloadScripts();
    if ($styles = static::getAsyncStyles()) {
      static::loadStylesAsync($styles);
    }
  }

  /**
   * Preloads footer scripts in head.
   */
  public static function preloadScripts(): void {
    global $wp_scripts;

    foreach ($wp_scripts->queue as $handle) {
      $script = $wp_scripts->registered[$handle];
      // Weird way to check if script is being enqueued in the footer.
      if (!isset($script->extra['group']) || $script->extra['group'] !== 1) {
        continue;
      }
      $source = static::getHandleSource($script);
      echo '<link rel="preload" href="', esc_attr($source), '" as="script" />', "\n";
    }
  }

  /**
   * Loads non critical CSS files asynchronously.
   */
  public static function loadStylesAsync(array $styles): void {
    global $wp_styles;

    foreach ($wp_styles->queue as $handle) {
      if (!in_array($handle, $styles, TRUE)) {
        continue;
      }

      $style = $wp_styles->registered[$handle];
      $source = static::getHandleSource($style);
      echo '<link rel="preload" href="', esc_attr($source), '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">', "\n";
      echo '<noscript><link rel="stylesheet" href="', esc_attr($source), '"></noscript>', "\n";
      wp_dequeue_style($handle);
    }
  }

  /**
   * Gets the source for a given script or style handle.
   *
   * @return string
   *   Script or style source.
   */
  public static function getHandleSource(\_WP_Dependency $handle): string {
    // If the version parameter is a Boolean value the WP version is appendend
    // to the script, nothing otherwise.
    if ($handle->ver === NULL) {
      $version = '';
    }
    else {
      $version = '?ver=' . ($handle->ver ? $handle->ver : Plugin::$version);
    }
    $source = $handle->src . $version;
    return $source;
  }

  /**
   * Loads scripts as deferred or async.
   *
   * @implements script_loader_tag
   */
  public static function script_loader_tag($tag, $handle) {
    $scripts_load = static::getAsyncScripts();

    if (isset($scripts_load[$handle]) && strpos($tag, ' defer ') === FALSE && strpos($tag, ' async ') === FALSE) {
      $tag = str_replace(' src=', sprintf(' %s src=', $scripts_load[$handle]), $tag);
    }

    return $tag;
  }

  /**
   * Prefetches DNS entries for particular resources.
   *
   * @implements wp_resource_hints
   */
  public static function wp_resource_hints($urls, $relation_type) {
    if ($relation_type === 'dns-prefetch') {
      $urls = array_merge($urls, static::getResourcesUrlPrefetch());
    }
    return $urls;
  }

  /**
   * Gets URLs of resources to prefetch.
   *
   * @return array
   *   URLs of resources to prefetch.
   */
  public static function getResourcesUrlPrefetch() {
    return apply_filters(Plugin::L10N . '/esources_url_prefetch', static::RESOURCES_URL_PREFETCH);
  }

  /**
   * Gets scripts handles to load async.
   *
   * @return array
   *   Handles of scripts to async loaded.
   */
  public static function getAsyncScripts(): array {
    return apply_filters(Plugin::L10N . '/scripts_async_load', static::SCRIPTS_ASYNC_LOAD);
  }

  /**
   * Gets styles handles to load async.
   *
   * @return array
   *   Handles of styles to async loaded.
   */
  public static function getAsyncStyles(): array {
    return apply_filters(Plugin::L10N . '/styles_async_load', static::SCRIPTS_ASYNC_LOAD);
  }

  /**
   * Gets scripts handles to dequeue.
   *
   * @return array
   *   Handles of scripts to be dequeued.
   */
  public static function getDequeueScripts(): array {
    return apply_filters(Plugin::L10N . '/scripts_dequeue', static::SCRIPTS_DEQUEUE);
  }

  /**
   * Gets styles handles to dequeue.
   *
   * @return array
   *   Handles of styles to be dequeued.
   */
  public static function getDequeueStyles(): array {
    return apply_filters(Plugin::L10N . '/styles_dequeue', static::STYLES_DEQUEUE);
  }

}
