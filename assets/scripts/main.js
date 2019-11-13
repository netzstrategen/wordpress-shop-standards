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

  // Single product sale label DOM element.
  const $singleProductSaleLabel = $('.single-product-summary .onsale');

  /**
   * Hides sale label if percentage value is lower than 10%.
   */
  $('.onsale').each(function hideSaleLabel() {
    if (parseInt($(this).text().replace(/^\D+/g, ''), 10) < 10) {
      $(this).hide();
    }
  });

  /**
   * Updates sale percentage value.
   *
   * @param {Integer} percentage
   *   The product/variation sale percentage.
   */
  function updateSaleLabel(percentage) {
    if (percentage >= 10) {
      $singleProductSaleLabel.text(`-${percentage}%`);
      $singleProductSaleLabel.show();
    } else {
      $singleProductSaleLabel.hide();
    }
  }

  $('.single_variation_wrap')
    .on('show_variation', (event, variation) => {
      if (variation.display_price < variation.display_regular_price) {
        // eslint-disable-next-line max-len
        const percentage = Math.floor((variation.display_regular_price - variation.display_price) / variation.display_regular_price * 100);
        updateSaleLabel(percentage);
      }

      // Updates discount table on product variation change.
      $('[data-variations]').parent().hide();
      $($('[data-variations]')).each(function updateDiscountTable() {
        if ($(this).data('variations').indexOf(variation.variation_id.toString()) !== -1) {
          $(this).parent().show();
        }
      });
    })
    .on('hide_variation', () => {
      const $fallbackPercentage = $singleProductSaleLabel.attr('data-sale-percentage');
      updateSaleLabel($fallbackPercentage);

      // Hides all variation product discount table on product variation hide.
      $($('[data-variations]')).each(function hideDiscountTable() {
        $(this).parent().hide();
      });
    });
}(jQuery));
