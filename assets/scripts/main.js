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

  // Disable checkout button if cart only contains low return products.
  $(document).on('ready updated_cart_totals', () => {
    if ($('.woocommerce-error li[data-plus-product="invalid"]').length) {
      $('.checkout-button').removeAttr('href').addClass('disabled');
      $('.wcppec-checkout-buttons').hide();
    }
  });

  // Used and defective goods checkbox agreement related functionality.
  $(document).ready(() => {
    const $usedGoodsConsentCheckbox = $('#used-goods-consent');
    const $usedGoodsConsentContainer = $('.product-defects__checkbox-container');
    if ($usedGoodsConsentCheckbox.length) {
      $(document)
        .on('show_variation', '.single_variation_wrap', (event, variation) => {
          const usedGoodsConsentAttr = variation.used_goods_consent_attribute;
          $('.product-defects__attribute').text(usedGoodsConsentAttr);
          if (usedGoodsConsentAttr) {
            $usedGoodsConsentContainer.show();
            $usedGoodsConsentCheckbox.prop('required', 'required');
          } else {
            $usedGoodsConsentContainer.hide();
            $usedGoodsConsentCheckbox.removeAttr('required');
          }
        });
    }
  });

  // Add ajax add to cart
  $(document).ready(() => {
    $('.single_add_to_cart_button').click((e) => {
      e.preventDefault();
      const $thisbutton = $(e.target);
      const $form = $thisbutton.closest('form.cart');
      const id = $thisbutton.val();
      const productQty = $form.find('input[name=quantity]').val() || 1;
      const productId = $form.find('input[name=product_id]').val() || id;
      const variationId = $form.find('input[name=variation_id]').val() || 0;
      const data = {
        action: 'woocommerceAjaxAddToCart',
        product_id: productId,
        product_sku: '',
        quantity: productQty,
        variation_id: variationId
      };
      $.ajax({
        type: 'post',
        url: shop_standards_settings.ajax_url,
        data,
        beforeSend() {
          $thisbutton.removeClass('added').addClass('loading');
        },
        complete() {
          $thisbutton.addClass('added').removeClass('loading');
        },
        success(response) {
          if (response.error && response.product_url) {
            window.location = response.product_url;
          } else {
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisbutton]);
            $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
            $form.closest('.product').before(response.fragments.notices_html);
            if ($('span.header-item--cart__amount').length) {
              $('span.header-item--cart__amount').html($(response.fragments['span.header-item--cart__amount']).html());
            } else {
              $('button.header-item--cart').append($(response.fragments['span.header-item--cart__amount']));
            }
          }
        },
      });
    });
  });
}(jQuery));
