jQuery(document).ready(function ($) {
  /**
   * Prevents multiple order to be sent.
   *
   * If the page takes time to be loaded, the user could click multiple
   * times on the place order button and this would generate multiple orders.
   * To prevent this we stop form submit event propagation after the first click.
   */
  $('form.checkout.wgm-second-checkout').submit(function (e) {
    if ($('#place_order').hasClass('disabled')) {
      e.preventDefault();
      return;
    }
    if ($('#terms').prop('checked') === true && $('#widerruf').prop('checked') === true) {
      $('#place_order').prop('value', placeOrder.button).addClass('disabled');
    }
  });
});
