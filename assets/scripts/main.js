/* global shop_standards_settings */

(function pageLoad($) {
  // Toggles product filtering by term.
  $('body')
    .on(
      'click',
      '.woocommerce-widget-layered-nav-list__item, .widget_layered_nav_filters .chosen, .shop-sidebar-widget .chosen',
      (e) => {
        const url = $(e.target).data('url');
        if (url) {
          document.location.href = url;
        }
      },
    );

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
    } else {
      // If there is only one option left in any of current variation attributes
      // dropdowns, it should be auto-selected.
      $variationsSelectDropdowns.each(function setVariationSelectDropdowns() {
        const $this = $(this);
        if ($this.find('option').size() === 2) {
          $this.val($this.find('option').eq(1).val());
        }
      });
    }
    // Ensure the rigth product image is displayed.
    // Some delay seems to be needed to refresh the product image.
    // We couldn't find a proper event to hook on, so we used a timeout.
    setTimeout(() => {
      $variationsForm.trigger('check_variations');
    }, 100);
  });

  // Single product sale label DOM element.
  const $singleProductSaleLabel = $('.single-product-summary .onsale');

  /**
   * Hides sale label if percentage value is lower than 10%.
   */
  $('.onsale').each(function hideSaleLabel() {
    const salePercentage = parseInt($(this).text().replace(/^\D+/g, ''), 10);
    if (salePercentage < shop_standards_settings.saleMinAmount) {
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
    if (percentage >= shop_standards_settings.saleMinAmount) {
      $singleProductSaleLabel.text(`-${percentage}%`);
      $singleProductSaleLabel.show();
    } else {
      $singleProductSaleLabel.hide();
    }
  }

  /**
   * Calculates the sale percentage applied to a product.
   *
   * @param {Integer} regularPrice
   *   Product regular price.
   * @param {Integer} salePrice
   *   Product discounted price.
   *
   * @return {Integer}
   *   Product sale precentage.
   */
  function calculateSalePercentage(regularPrice, salePrice) {
    return Math.floor((regularPrice - salePrice) / regularPrice * 100);
  }

  $('.single_variation_wrap')
    .on('show_variation', (event, variation) => {
      /* eslint-disable max-len */
      const percentage = calculateSalePercentage(variation.display_regular_price, variation.display_price);
      updateSaleLabel(percentage);
      /* eslint-enable max-len */

      // Updates discount table on product variation change.
      $('[data-variations]').parent().hide();
      $($('[data-variations]')).each(function updateDiscountTable() {
        if ($(this).data('variations').indexOf(variation.variation_id.toString()) !== -1) {
          $(this).parent().show();
        }
      });
    })
    .on('hide_variation', () => {
      // Displays the lowest sale percentage.
      updateSaleLabel($singleProductSaleLabel.data('sale-percentage'));

      // Hides all variation product discount table on product variation hide.
      $($('[data-variations]')).each(function hideDiscountTable() {
        $(this).parent().hide();
      });
    });

  $(document).ready(() => {
    // Disable copy/paste actions on billing email fields.
    if (shop_standards_settings.emailConfirmationEmail === 'yes') {
      $('#billing_email, #billing_email_confirmation').each(function() {
        // eslint-disable-next-line max-nested-callbacks
        $(this).on('cut copy paste', (e) => {
          e.preventDefault();
        });
      });

      // Marks email confirmation field as invalid if does not match email
      // field on form submit.
      $('form.woocommerce-checkout').on('input validate change', () => {
        if ($('#billing_email').val() !== $('#billing_email_confirmation').val()) {
          $('#billing_email_confirmation_field').removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-required-field');
        }
      });
    }
  });

  // Disable checkout button if there are any WooCommerce error displayed.
  $(document).on('ready updated_cart_totals', () => {
    if ($('.woocommerce-error').length) {
      $('.checkout-button').removeAttr('href').addClass('disabled');
    }
  });
}(jQuery));
