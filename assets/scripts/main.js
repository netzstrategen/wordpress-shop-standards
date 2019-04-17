(function pageLoad($) {
  /**
   * Prevents multiple order to be sent.
   *
   * If the page takes time to be loaded, the user could click multiple
   * times on the place order button and this would generate multiple orders.
   * To prevent this we stop form submit event propagation after the first click.
   *
   * Covers the case of woocommerce-german-market second checkout page being
   * disabled. If form fields validation fails, the order submit button is
   * enabled again to allow the user to resend the form.
   */

  const placeOrderBtnInitialValue = $('#place_order').prop('value');
  $('#place_order').click(function disableSubmitOrder(e) {
    if ($(this).hasClass('disabled')) {
      e.preventDefault();
      return;
    }
    $('#place_order').prop('value', 'Ihre Bestellung wird jetzt verarbeitet…').addClass('disabled');
  });
  $(document.body).on('checkout_error', () => {
    $('#place_order').prop('value', placeOrderBtnInitialValue).removeClass('disabled');
  });
}(jQuery));
