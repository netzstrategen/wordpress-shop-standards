<?php

/**
 * @file
 * Contains \Netzstrategen\ShopStandards\UsedGoods.
 */

namespace Netzstrategen\ShopStandards;

/**
 * Used or defective goods user consent checkbox functionality.
 */
class UsedGoods {

  /**
   * Checkbox consent initialization method.
   */
  public static function init() {
    add_action('woocommerce_before_add_to_cart_button', __CLASS__ . '::displayCheckbox');
  }

  /**
   * Displays the checkbox and agreement text.
   */
  public static function displayCheckbox() {
    echo '<div>Checkbox</div>';
  }

}
