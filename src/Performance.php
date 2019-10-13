<?php

namespace Netzstrategen\ShopStandards;

/**
 * Performance improvements.
 */
class Performance {

  /**
   * WooCommerce scripts to be defer or async loaded.
   *
   * @var string
   */
  const SCRIPTS_LOAD = [
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
   * Loads scripts as deferred or async.
   *
   * @implements script_loader_tag
   */
  public static function script_loader_tag($tag, $handle) {
    $scripts_load = apply_filters(Plugin::L10N . '/scripts_load', static::SCRIPTS_LOAD);

    if (isset($scripts_load[$handle]) && strpos($tag, ' defer ') === FALSE && strpos($tag, ' async ') === FALSE) {
      $tag = str_replace(' src=', sprintf(' %s src=', $scripts_load[$handle]), $tag);
    }

    return $tag;
  }

}
