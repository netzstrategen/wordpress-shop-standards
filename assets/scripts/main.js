(function pageLoad($) {
  /**
   * Prevents multiple order to be sent.
   *
   * If the page takes time to be loaded, the user could click multiple
   * times on the place order button and this would generate multiple orders.
   * To prevent this we stop form submit event propagation after the first click.
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

  const $variationsForm = $('.variations_form');
  const $variationsSelectDropdowns = $('.variations_form .variations select');
  let $variationSelectChanged = false;

  $variationsSelectDropdowns.change(function variationSelectDropdownChanged() {
    $variationSelectChanged = $(this);
  });

  $('.variations_form').on('woocommerce_variation_has_changed', () => {
    // Allow selecting the default empty value of an attributes dropdown
    // without modifying the value of the others.
    if ($variationSelectChanged && $variationSelectChanged.val() === '') {
      $variationSelectChanged.val('');
      $variationSelectChanged = false;
      $variationsForm.trigger('check_variations');
    } else {
      // If there is only one option left in any of current variation attributes
      // dropdowns, it should be auto-selected.
      $variationsSelectDropdowns.each(function setVariationSelectDropdowns() {
        const $this = $(this);
        if ($this.find('option').size() === 2) {
          $this.val($this.find('option').eq(1).val());
        }
        $variationsForm.trigger('check_variations');
      });
    }
  });

  /**
   * Tracks clicks on WooCommerce product gallery and sends event to Google Analytics.
   */
  $(document).on('click', '.single-product .woocommerce-product-gallery', (event) => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'UniversalEvent',
      eventCategory: `Product | Image | ${document.documentElement.getAttribute('data-product-name')} `,
      eventAction: 'Impression',
      eventLabel: event.target.href,
    });
  });

  $(document).on('click', '.single-product .lg-prev', () => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'UniversalEvent',
      eventCategory: `Product | Image | ${document.documentElement.getAttribute('data-product-name')} `,
      eventAction: 'Impression',
      eventLabel: $('.lg-prev-slide .lg-image').attr('src'),
    });
  });

  $(document).on('click', '.single-product .lg-next', () => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'UniversalEvent',
      eventCategory: `Product | Image | ${document.documentElement.getAttribute('data-product-name')} `,
      eventAction: 'Impression',
      eventLabel: $('.lg-next-slide .lg-image').attr('src'),
    });
  });
}(jQuery));
