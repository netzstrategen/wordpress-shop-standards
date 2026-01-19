<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\Elementor\DynamicTags.
 */

namespace Netzstrategen\ShopStandards\Elementor;

/**
 * Registers Elementor dynamic tags.
 */
class DynamicTags {

  /**
   * Registers custom dynamic tags.
   *
   * @implements elementor/dynamic_tags/register
   *
   * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags
   *   Dynamic tags manager instance.
   */
  public static function init($dynamic_tags) {
    $dynamic_tags->register(new DynamicTags\ProductAttributeTag());
  }

}
