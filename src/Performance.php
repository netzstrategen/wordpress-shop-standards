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
  const ASYNC_LOAD_SCRIPTS = [
    'select2' => ['async' => TRUE, 'defer' => FALSE],
    'selectWoo' => ['async' => TRUE, 'defer' => FALSE],
    'jquery-blockui' => ['async' => FALSE, 'defer' => TRUE],
    'js-cookie' => ['async' => FALSE, 'defer' => TRUE],
    'wc-add-to-cart' => ['async' => FALSE, 'defer' => TRUE],
    'wc-cart-fragments' => ['async' => FALSE, 'defer' => TRUE],
    'wc-country-select' => ['async' => FALSE, 'defer' => TRUE],
    'woocommerce' => ['async' => FALSE, 'defer' => TRUE],
  ];

  /**
   * Styles to be defer or async loaded.
   *
   * @var array
   */
  const ASYNC_LOAD_STYLES = [];

  /**
   * Scripts to be dequeued.
   *
   * @var array
   */
  const DEQUEUE_SCRIPTS = [];

  /**
   * Styles to be dequeued.
   *
   * @var array
   */
  const DEQUEUE_STYLES = [];

  /**
   * URLs of resources to prefetch.
   *
   * @var array
   */
  const PREFETCH_RESOURCES_URL = [];

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
    if ($styles = static::getAsyncLoadStyles()) {
      static::asyncLoadStyles($styles);
    }
  }

  /**
   * Preloads footer scripts in head.
   */
  public static function preloadScripts(): void {
    global $wp_scripts;

    foreach ($wp_scripts->queue as $handle) {
      $script = $wp_scripts->registered[$handle] ?? [];
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
  public static function asyncLoadStyles(array $styles): void {
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
      $version = '?ver=' . Plugin::getGitVersion();
    }
    $source = $handle->src . $version;
    return $source;
  }

  /**
   * Loads scripts as deferred, async or both.
   *
   * @implements script_loader_tag
   */
  public static function script_loader_tag($tag, $handle) {
    $scripts_load = static::getAsyncLoadScripts();

    if (!isset($scripts_load[$handle]) || strpos($tag, ' defer ') !== FALSE || strpos($tag, ' async ') !== FALSE) {
      return $tag;
    }

    $load_modes = [];
    foreach ($scripts_load[$handle] as $key => $value) {
      if ($value) {
        $load_modes[] = $key;
      }
    }
    $tag = str_replace(' src=', sprintf(' %s src=', implode(' ', $load_modes)), $tag);

    return $tag;
  }

  /**
   * Prefetches DNS entries for particular resources.
   *
   * @implements wp_resource_hints
   */
  public static function wp_resource_hints($urls, $relation_type) {
    if ($relation_type === 'dns-prefetch') {
      $urls = array_merge($urls, static::getPrefetchResourcesUrl());
    }
    return $urls;
  }

  /**
   * Gets URLs of resources to prefetch.
   *
   * @return array
   *   URLs of resources to prefetch.
   */
  public static function getPrefetchResourcesUrl() {
    return apply_filters(Plugin::L10N . '/prefetch_resources_url', static::PREFETCH_RESOURCES_URL);
  }

  /**
   * Gets scripts handles to load async.
   *
   * @return array
   *   Handles of scripts to async loaded.
   */
  public static function getAsyncLoadScripts(): array {
    return apply_filters(Plugin::L10N . '/async_load_scripts', static::ASYNC_LOAD_SCRIPTS);
  }

  /**
   * Gets styles handles to load async.
   *
   * @return array
   *   Handles of styles to async loaded.
   */
  public static function getAsyncLoadStyles(): array {
    return apply_filters(Plugin::L10N . '/async_load_styles', static::ASYNC_LOAD_STYLES);
  }

  /**
   * Gets scripts handles to dequeue.
   *
   * @return array
   *   Handles of scripts to be dequeued.
   */
  public static function getDequeueScripts(): array {
    return apply_filters(Plugin::L10N . '/dequeue_scripts', static::DEQUEUE_SCRIPTS);
  }

  /**
   * Gets styles handles to dequeue.
   *
   * @return array
   *   Handles of styles to be dequeued.
   */
  public static function getDequeueStyles(): array {
    return apply_filters(Plugin::L10N . '/dequeue_styles', static::DEQUEUE_STYLES);
  }

}
