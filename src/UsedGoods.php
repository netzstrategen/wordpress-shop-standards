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
    $test = 'test attribute';
    echo '<div class="checkbox-container">
      <b data-checkbox-detail-expand>Hinweis und gesonderte Vereinbarung zu den Abweichungen von den objektiven Anforderungen</b>
      <div class="checkbox-detail">
        <p>Hinweis: Bitte beachten Sie, dass Sie die Ware nicht in den Warenkorb legen können, wenn Sie mit der Vereinbarung nicht einverstanden sind. Ihr Einverständnis mit der Vereinbarung erklären Sie durch Anklicken der Checkbox.</p>
        <div class="checkbox-text-container">
          <input data-checkbox-toggle class="checkbox-box" type="checkbox" id="used-goods-consent" name="used-goods-consent" value="used-goods-consent-submit">
          <div class="checkbox-text">
            <p>Es wird vereinbart, dass die hier angebotene Ware in den folgenden Merkmalen in ihrer Beschaffenheit von den objektiven Anforderungen abweicht und ein Mängelgewährleistungsrecht diesbezüglich ausgeschlossen ist:</p>
            <p>-(z.B.: ' . $test . ')</p>
          </div>
        </div>
      </div>
    </div>';
  }

}
