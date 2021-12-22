/* global jQuery, shop_standards_settings */

(function pageLoad($) {
  // Toggles product filtering by term.
  $('body')
    .on(
      'click',
      '[data-url]',
      (e) => {
        const url = $(e.target).data('url') || $(e.target).closest('[data-url]').data('url');
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

  $('.single_variation_wrap')
    .on('show_variation', (event, variation) => {
      // Updates discount table on product variation change.
      $('[data-variations]').parent().hide();
      $($('[data-variations]')).each(function updateDiscountTable() {
        if ($(this).data('variations').indexOf(variation.variation_id.toString()) !== -1) {
          $(this).parent().show();
        }
      });
    })
    .on('hide_variation', () => {
      // Hides all variation product discount table on product variation hide.
      $($('[data-variations]')).each(function hideDiscountTable() {
        $(this).parent().hide();
      });
    });

  $(document).ready(() => {
    // Disable copy/paste actions on billing email fields.
    if (shop_standards_settings.emailConfirmationEmail === 'yes') {
      $('#billing_email, #billing_email_confirmation').on('cut copy paste', (e) => {
        e.preventDefault();
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
      $('.wcppec-checkout-buttons').hide();
    }
  });

  // Used and defective goods checkbox agreement related functionality.
  $('[data-checkbox-detail-expand]').click(() => {
    $('.checkbox-detail').toggleClass('expand');
  });

  $('[data-checkbox-toggle]').click(() => {
    $('.single_add_to_cart_button').toggleClass('disabled');
  });
}(jQuery));
