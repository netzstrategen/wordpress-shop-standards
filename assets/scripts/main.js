(function pageLoad($) {
  /**
   * Prevents multiple order to be sent.
   *
   * If the page takes time to be loaded, the user could click multiple
   * times on the place order button and this would generate multiple orders.
   * To prevent this we stop form submit event propagation after the first click.
   */
  $('form.checkout.wgm-second-checkout').submit((e) => {
    if ($('#place_order').hasClass('disabled')) {
      e.preventDefault();
      return;
    }
    if ($('#terms').prop('checked') === true && $('#shipping-service-provider').prop('checked') === true) {
      $('#place_order').prop('value', 'Ihre Bestellung wird jetzt verarbeitet…').addClass('disabled');
    }
  });

  /**
   * Prevents multiple order to be sent even if woocommerce-german-market second
   * checkout page is disabled. If form fields validation fails, the order
   * submit button is enabled again to allow the user to resend the form.
   */
  $('#place_order').click(function (e) {
    if ($(this).hasClass('disabled')) {
      e.preventDefault();
      return;
    }
    $(this).addClass('disabled');
  });
  $(document.body).on('checkout_error', (data) => {
    $('#place_order').removeClass('disabled');
  });
}(jQuery));
