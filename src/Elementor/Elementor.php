<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Elementor\Elementor.
 */

namespace Netzstrategen\ShopStandards\Elementor;

/**
 * Elementor integration.
 */
class Elementor {

  /**
   * Initializes Elementor integration.
   *
   * @implements init
   */
  public static function init() {
    if (!did_action('elementor/loaded')) {
      return;
    }

    add_action('elementor/dynamic_tags/register', __NAMESPACE__ . '\DynamicTags::init');
  }

}
