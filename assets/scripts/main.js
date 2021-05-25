/* global jQuery, shop_standards_settings */

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
      $('#billing_email, #billing_email_confirmation').each(() => {
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
    $('form.woocommerce-checkout').on('input', '#billing_salutation, #shipping_salutation, #woocommerce_eu_vat_number', (e) => {
      const billingSalutation = $('#billing_salutation').val();
      const shippingSalutation = $('#shipping_salutation').val();
      const billingVat = $('#woocommerce_eu_vat_number');
      if (billingSalutation === 'Company' && billingVat.val() && shippingSalutation !== billingSalutation) {
        $('#shipping_salutation')
          .val($('#billing_salutation').val())
          .css('pointer-events', 'none')
          .trigger('change');
        $('#shipping_salutation option:not(:selected)').prop('disabled', true);
      } else if (e.target.name === 'billing_salutation' && billingVat.val()) {
        $('#shipping_salutation').css('pointer-events', 'initial');
        $('#shipping_salutation option:not(:selected)').prop('disabled', false);
        billingVat.val('');
      }
      if (e.target.name.indexOf('salutation') !== -1) {
        $(document.body).trigger('update_checkout');
      }
    });
  });

  // Disable checkout button if there are any WooCommerce error displayed.
  $(document).on('ready updated_cart_totals', () => {
    if ($('.woocommerce-error').length) {
      $('.checkout-button').removeAttr('href').addClass('disabled');
      $('.wcppec-checkout-buttons').hide();
    }
  });

  // Fixes missing anchor on elementor widget pagination.
  // https://github.com/elementor/elementor/issues/4703
  $(document).ready(() => {
    const $widget = $('.elementor-widget-woocommerce-products');
    if (!$widget.attr('id')) {
      $widget.attr('id', $widget.attr('data-id'));
    }
    $('.elementor-widget-woocommerce-products .page-numbers a').each((i, a) => {
      $(a).attr('href', `${$(a).attr('href')}#${$(a).closest($widget).attr('id')}`);
      // Remove event listeners to prevent Elementor from treating links as anchor target links.
      $(a).off('click').on('click', (e) => e.stopImmediatePropagation());
    });
  });
}(jQuery));
