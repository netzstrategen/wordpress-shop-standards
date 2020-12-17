"use strict";

(function pageLoad($, ajaxurl, shopStandardsAdmin) {
  /**
   * Checks GTIN for uniqueness and issues an error/success message.
   */
  $(document).on('keyup', "input[name*='_shop-standards_gtin']", _.debounce(function (event) {
    var $gtinInput = $(event.currentTarget);
    var $gtinField = $gtinInput.parents('.form-field');
    $gtinField.find('.notice').remove();

    if ($gtinField.find('.spinner').length === 0) {
      $gtinField.append('<span class="spinner"></span>');
    }

    $gtinField.find('.spinner').addClass('is-active');
    jQuery.post(ajaxurl, {
      action: 'is_existing_gtin',
      product_id: shopStandardsAdmin.product_id,
      gtin: $gtinInput.val()
    }, function (response) {
      $gtinField.find('.spinner').removeClass('is-active');
      $gtinField.find('.notice').remove();

      if (response.is_unique) {
        var message = shopStandardsAdmin.gtin_success_message;
        $gtinField.append("\n            <div class=\"notice notice-success\">\n              ".concat(message, "\n            </div>\n          "));
      } else {
        var _message = shopStandardsAdmin.gtin_error_message.replace('{{url}}', response.duplicate_edit_link);

        $gtinField.append("\n            <div class=\"notice notice-error\">\n              ".concat(_message, "\n            </div>\n          "));
      }
    }, 'json');
  }, 500));
})(jQuery, window.ajaxurl, window.shop_standards_admin);
"use strict";

/* global shop_standards_settings */
(function pageLoad($) {
  // Toggles product filtering by term.
  $('body').on('click', '.woocommerce-widget-layered-nav-list__item, .widget_layered_nav_filters .chosen, .shop-sidebar-widget .chosen', function (e) {
    var url = $(e.target).data('url');

    if (url) {
      document.location.href = url;
    }
  });
  /**
   * Prevents multiple order to be sent.
   *
   * If the page takes time to be loaded, the user could click multiple
   * times on the place order button and this would generate multiple orders.
   * To prevent this we stop form submit event propagation after the first click.
   */

  var placeOrderBtnInitialValue = $('#place_order').prop('value');
  $('#place_order').click(function disableSubmitOrder(e) {
    if ($(this).hasClass('disabled')) {
      e.preventDefault();
      return;
    }

    $('#place_order').prop('value', 'Ihre Bestellung wird jetzt verarbeitet…').addClass('disabled');
  });
  $(document.body).on('checkout_error', function () {
    $('#place_order').prop('value', placeOrderBtnInitialValue).removeClass('disabled');
  });
  var $variationsForm = $('.variations_form');
  var $variationsSelectDropdowns = $('.variations_form .variations select');
  var $variationSelectChanged = false;
  $variationsSelectDropdowns.change(function variationSelectDropdownChanged() {
    $variationSelectChanged = $(this);
  });
  $('.variations_form').on('woocommerce_variation_has_changed', function () {
    // Allow selecting the default empty value of an attributes dropdown
    // without modifying the value of the others.
    if ($variationSelectChanged && $variationSelectChanged.val() === '') {
      $variationSelectChanged.val('');
      $variationSelectChanged = false;
    } else {
      // If there is only one option left in any of current variation attributes
      // dropdowns, it should be auto-selected.
      $variationsSelectDropdowns.each(function setVariationSelectDropdowns() {
        var $this = $(this);

        if ($this.find('option').size() === 2) {
          $this.val($this.find('option').eq(1).val());
        }
      });
    } // Ensure the rigth product image is displayed.
    // Some delay seems to be needed to refresh the product image.
    // We couldn't find a proper event to hook on, so we used a timeout.


    setTimeout(function () {
      $variationsForm.trigger('check_variations');
    }, 100);
  });
  $('.single_variation_wrap').on('show_variation', function (event, variation) {
    // Updates discount table on product variation change.
    $('[data-variations]').parent().hide();
    $($('[data-variations]')).each(function updateDiscountTable() {
      if ($(this).data('variations').indexOf(variation.variation_id.toString()) !== -1) {
        $(this).parent().show();
      }
    });
  }).on('hide_variation', function () {
    // Hides all variation product discount table on product variation hide.
    $($('[data-variations]')).each(function hideDiscountTable() {
      $(this).parent().hide();
    });
  });
  $(document).ready(function () {
    // Disable copy/paste actions on billing email fields.
    if (shop_standards_settings.emailConfirmationEmail === 'yes') {
      $('#billing_email, #billing_email_confirmation').each(function x() {
        // eslint-disable-next-line max-nested-callbacks
        $(this).on('cut copy paste', function (e) {
          e.preventDefault();
        });
      }); // Marks email confirmation field as invalid if does not match email
      // field on form submit.

      $('form.woocommerce-checkout').on('input validate change', function () {
        if ($('#billing_email').val() !== $('#billing_email_confirmation').val()) {
          $('#billing_email_confirmation_field').removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-required-field');
        }
      });
    }
  }); // Disable checkout button if there are any WooCommerce error displayed.

  $(document).on('ready updated_cart_totals', function () {
    if ($('.woocommerce-error').length) {
      $('.checkout-button').removeAttr('href').addClass('disabled');
      $('.wcppec-checkout-buttons').hide();
    }
  });
})(jQuery);