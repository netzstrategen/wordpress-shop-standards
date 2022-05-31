/* global jQuery, shop_standards_settings */
(function pageLoad($) {
  // Toggles product filtering by term.
  $('body').on('click', '[data-url]', function (e) {
    var url = $(e.target).data('url') || $(e.target).closest('[data-url]').data('url');

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

        if ($this.find('option').length === 2) {
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
      $('#billing_email, #billing_email_confirmation').on('cut copy paste', function (e) {
        e.preventDefault();
      }); // Marks email confirmation field as invalid if does not match email
      // field on form submit.

      $('form.woocommerce-checkout').on('input validate change', function () {
        if ($('#billing_email').val() !== $('#billing_email_confirmation').val()) {
          $('#billing_email_confirmation_field').removeClass('woocommerce-validated').addClass('woocommerce-invalid woocommerce-invalid-required-field');
        }
      });
    }
  }); // Disable checkout button if cart only contains low return products.

  $(document).on('ready updated_cart_totals', function () {
    if ($('.woocommerce-error li[data-plus-product="invalid"]').length) {
      $('.checkout-button').removeAttr('href').addClass('disabled');
      $('.wcppec-checkout-buttons').hide();
    }
  }); // Used and defective goods checkbox agreement related functionality.

  $(document).ready(function () {
    var $usedGoodsConsentCheckbox = $('#used-goods-consent');
    var $usedGoodsConsentContainer = $('.product-defects__checkbox-container');

    if ($usedGoodsConsentCheckbox.length) {
      $(document).on('show_variation', '.single_variation_wrap', function (event, variation) {
        var usedGoodsConsentAttr = variation.used_goods_consent_attribute;
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
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOlsicGFnZUxvYWQiLCIkIiwib24iLCJlIiwidXJsIiwidGFyZ2V0IiwiZGF0YSIsImNsb3Nlc3QiLCJkb2N1bWVudCIsImxvY2F0aW9uIiwiaHJlZiIsInBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUiLCJwcm9wIiwiY2xpY2siLCJkaXNhYmxlU3VibWl0T3JkZXIiLCJoYXNDbGFzcyIsInByZXZlbnREZWZhdWx0IiwiYWRkQ2xhc3MiLCJib2R5IiwicmVtb3ZlQ2xhc3MiLCIkdmFyaWF0aW9uc0Zvcm0iLCIkdmFyaWF0aW9uc1NlbGVjdERyb3Bkb3ducyIsIiR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkIiwiY2hhbmdlIiwidmFyaWF0aW9uU2VsZWN0RHJvcGRvd25DaGFuZ2VkIiwidmFsIiwiZWFjaCIsInNldFZhcmlhdGlvblNlbGVjdERyb3Bkb3ducyIsIiR0aGlzIiwiZmluZCIsImxlbmd0aCIsImVxIiwic2V0VGltZW91dCIsInRyaWdnZXIiLCJldmVudCIsInZhcmlhdGlvbiIsInBhcmVudCIsImhpZGUiLCJ1cGRhdGVEaXNjb3VudFRhYmxlIiwiaW5kZXhPZiIsInZhcmlhdGlvbl9pZCIsInRvU3RyaW5nIiwic2hvdyIsImhpZGVEaXNjb3VudFRhYmxlIiwicmVhZHkiLCJzaG9wX3N0YW5kYXJkc19zZXR0aW5ncyIsImVtYWlsQ29uZmlybWF0aW9uRW1haWwiLCJyZW1vdmVBdHRyIiwiJHVzZWRHb29kc0NvbnNlbnRDaGVja2JveCIsIiR1c2VkR29vZHNDb25zZW50Q29udGFpbmVyIiwidXNlZEdvb2RzQ29uc2VudEF0dHIiLCJ1c2VkX2dvb2RzX2NvbnNlbnRfYXR0cmlidXRlIiwidGV4dCIsImpRdWVyeSJdLCJtYXBwaW5ncyI6IkFBQUE7QUFFQyxVQUFTQSxRQUFULENBQWtCQyxDQUFsQixFQUFxQjtBQUNwQjtBQUNBQSxFQUFBQSxDQUFDLENBQUMsTUFBRCxDQUFELENBQ0dDLEVBREgsQ0FFSSxPQUZKLEVBR0ks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QUFNZSxvQkFBb0IsR0FBR2YsU0FBUyxDQUFDZ0IsNEJBQXZDO0FBQ0FsRCxRQUFBQSxDQUFDLENBQUMsNkJBQUQsQ0FBRCxDQUFpQ21ELElBQWpDLENBQXNDRixvQkFBdEM7O0FBQ0EsWUFBSUEsb0JBQUosRUFBMEI7QUFDeEJELFVBQUFBLDBCQUEwQixDQUFDUCxJQUEzQjtBQUNBTSxVQUFBQSx5QkFBeUIsQ0FBQ3BDLElBQTFCLENBQStCLFVBQS9CLEVBQTJDLFVBQTNDO0FBQ0QsU0FIRCxNQUdPO0FBQ0xxQyxVQUFBQSwwQkFBMEIsQ0FBQ1osSUFBM0I7QUFDQVcsVUFBQUEseUJBQXlCLENBQUNELFVBQTFCLENBQXFDLFVBQXJDO0FBQ0Q7QUFDRixPQVhIO0FBWUQ7QUFDRixHQWpCRDtBQWtCRCxDQS9IQSxFQStIQ00sTUEvSEQsQ0FBRCIsInNvdXJjZXNDb250ZW50IjpbIi8qIGdsb2JhbCBqUXVlcnksIHNob3Bfc3RhbmRhcmRzX3NldHRpbmdzICovXG5cbihmdW5jdGlvbiBwYWdlTG9hZCgkKSB7XG4gIC8vIFRvZ2dsZXMgcHJvZHVjdCBmaWx0ZXJpbmcgYnkgdGVybS5cbiAgJCgnYm9keScpXG4gICAgLm9uKFxuICAgICAgJ2NsaWNrJyxcbiAgICAgICdbZGF0YS11cmxdJyxcbiAgICAgIChlKSA9PiB7XG4gICAgICAgIGNvbnN0IHVybCA9ICQoZS50YXJnZXQpLmRhdGEoJ3VybCcpIHx8ICQoZS50YXJnZXQpLmNsb3Nlc3QoJ1tkYXRhLXVybF0nKS5kYXRhKCd1cmwnKTtcbiAgICAgICAgaWYgKHVybCkge1xuICAgICAgICAgIGRvY3VtZW50LmxvY2F0aW9uLmhyZWYgPSB1cmw7XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgKTtcblxuICAvKipcbiAgICogUHJldmVudHMgbXVsdGlwbGUgb3JkZXIgdG8gYmUgc2VudC5cbiAgICpcbiAgICogSWYgdGhlIHBhZ2UgdGFrZXMgdGltZSB0byBiZSBsb2FkZWQsIHRoZSB1c2VyIGNvdWxkIGNsaWNrIG11bHRpcGxlXG4gICAqIHRpbWVzIG9uIHRoZSBwbGFjZSBvcmRlciBidXR0b24gYW5kIHRoaXMgd291bGQgZ2VuZXJhdGUgbXVsdGlwbGUgb3JkZXJzLlxuICAgKiBUbyBwcmV2ZW50IHRoaXMgd2Ugc3RvcCBmb3JtIHN1Ym1pdCBldmVudCBwcm9wYWdhdGlvbiBhZnRlciB0aGUgZmlyc3QgY2xpY2suXG4gICAqL1xuXG4gIGNvbnN0IHBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUgPSAkKCcjcGxhY2Vfb3JkZXInKS5wcm9wKCd2YWx1ZScpO1xuICAkKCcjcGxhY2Vfb3JkZXInKS5jbGljayhmdW5jdGlvbiBkaXNhYmxlU3VibWl0T3JkZXIoZSkge1xuICAgIGlmICgkKHRoaXMpLmhhc0NsYXNzKCdkaXNhYmxlZCcpKSB7XG4gICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICByZXR1cm47XG4gICAgfVxuICAgICQoJyNwbGFjZV9vcmRlcicpLnByb3AoJ3ZhbHVlJywgJ0locmUgQmVzdGVsbHVuZyB3aXJkIGpldHp0IHZlcmFyYmVpdGV04oCmJykuYWRkQ2xhc3MoJ2Rpc2FibGVkJyk7XG4gIH0pO1xuICAkKGRvY3VtZW50LmJvZHkpLm9uKCdjaGVja291dF9lcnJvcicsICgpID0+IHtcbiAgICAkKCcjcGxhY2Vfb3JkZXInKS5wcm9wKCd2YWx1ZScsIHBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUpLnJlbW92ZUNsYXNzKCdkaXNhYmxlZCcpO1xuICB9KTtcblxuICBjb25zdCAkdmFyaWF0aW9uc0Zvcm0gPSAkKCcudmFyaWF0aW9uc19mb3JtJyk7XG4gIGNvbnN0ICR2YXJpYXRpb25zU2VsZWN0RHJvcGRvd25zID0gJCgnLnZhcmlhdGlvbnNfZm9ybSAudmFyaWF0aW9ucyBzZWxlY3QnKTtcbiAgbGV0ICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkID0gZmFsc2U7XG5cbiAgJHZhcmlhdGlvbnNTZWxlY3REcm9wZG93bnMuY2hhbmdlKGZ1bmN0aW9uIHZhcmlhdGlvblNlbGVjdERyb3Bkb3duQ2hhbmdlZCgpIHtcbiAgICAkdmFyaWF0aW9uU2VsZWN0Q2hhbmdlZCA9ICQodGhpcyk7XG4gIH0pO1xuXG4gICQoJy52YXJpYXRpb25zX2Zvcm0nKS5vbignd29vY29tbWVyY2VfdmFyaWF0aW9uX2hhc19jaGFuZ2VkJywgKCkgPT4ge1xuICAgIC8vIEFsbG93IHNlbGVjdGluZyB0aGUgZGVmYXVsdCBlbXB0eSB2YWx1ZSBvZiBhbiBhdHRyaWJ1dGVzIGRyb3Bkb3duXG4gICAgLy8gd2l0aG91dCBtb2RpZnlpbmcgdGhlIHZhbHVlIG9mIHRoZSBvdGhlcnMuXG4gICAgaWYgKCR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkICYmICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkLnZhbCgpID09PSAnJykge1xuICAgICAgJHZhcmlhdGlvblNlbGVjdENoYW5nZWQudmFsKCcnKTtcbiAgICAgICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkID0gZmFsc2U7XG4gICAgfSBlbHNlIHtcbiAgICAgIC8vIElmIHRoZXJlIGlzIG9ubHkgb25lIG9wdGlvbiBsZWZ0IGluIGFueSBvZiBjdXJyZW50IHZhcmlhdGlvbiBhdHRyaWJ1dGVzXG4gICAgICAvLyBkcm9wZG93bnMsIGl0IHNob3VsZCBiZSBhdXRvLXNlbGVjdGVkLlxuICAgICAgJHZhcmlhdGlvbnNTZWxlY3REcm9wZG93bnMuZWFjaChmdW5jdGlvbiBzZXRWYXJpYXRpb25TZWxlY3REcm9wZG93bnMoKSB7XG4gICAgICAgIGNvbnN0ICR0aGlzID0gJCh0aGlzKTtcbiAgICAgICAgaWYgKCR0aGlzLmZpbmQoJ29wdGlvbicpLmxlbmd0aCA9PT0gMikge1xuICAgICAgICAgICR0aGlzLnZhbCgkdGhpcy5maW5kKCdvcHRpb24nKS5lcSgxKS52YWwoKSk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH1cbiAgICAvLyBFbnN1cmUgdGhlIHJpZ3RoIHByb2R1Y3QgaW1hZ2UgaXMgZGlzcGxheWVkLlxuICAgIC8vIFNvbWUgZGVsYXkgc2VlbXMgdG8gYmUgbmVlZGVkIHRvIHJlZnJlc2ggdGhlIHByb2R1Y3QgaW1hZ2UuXG4gICAgLy8gV2UgY291bGRuJ3QgZmluZCBhIHByb3BlciBldmVudCB0byBob29rIG9uLCBzbyB3ZSB1c2VkIGEgdGltZW91dC5cbiAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICR2YXJpYXRpb25zRm9ybS50cmlnZ2VyKCdjaGVja192YXJpYXRpb25zJyk7XG4gICAgfSwgMTAwKTtcbiAgfSk7XG5cbiAgJCgnLnNpbmdsZV92YXJpYXRpb25fd3JhcCcpXG4gICAgLm9uKCdzaG93X3ZhcmlhdGlvbicsIChldmVudCwgdmFyaWF0aW9uKSA9PiB7XG4gICAgICAvLyBVcGRhdGVzIGRpc2NvdW50IHRhYmxlIG9uIHByb2R1Y3QgdmFyaWF0aW9uIGNoYW5nZS5cbiAgICAgICQoJ1tkYXRhLXZhcmlhdGlvbnNdJykucGFyZW50KCkuaGlkZSgpO1xuICAgICAgJCgkKCdbZGF0YS12YXJpYXRpb25zXScpKS5lYWNoKGZ1bmN0aW9uIHVwZGF0ZURpc2NvdW50VGFibGUoKSB7XG4gICAgICAgIGlmICgkKHRoaXMpLmRhdGEoJ3ZhcmlhdGlvbnMnKS5pbmRleE9mKHZhcmlhdGlvbi52YXJpYXRpb25faWQudG9TdHJpbmcoKSkgIT09IC0xKSB7XG4gICAgICAgICAgJCh0aGlzKS5wYXJlbnQoKS5zaG93KCk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH0pXG4gICAgLm9uKCdoaWRlX3ZhcmlhdGlvbicsICgpID0+IHtcbiAgICAgIC8vIEhpZGVzIGFsbCB2YXJpYXRpb24gcHJvZHVjdCBkaXNjb3VudCB0YWJsZSBvbiBwcm9kdWN0IHZhcmlhdGlvbiBoaWRlLlxuICAgICAgJCgkKCdbZGF0YS12YXJpYXRpb25zXScpKS5lYWNoKGZ1bmN0aW9uIGhpZGVEaXNjb3VudFRhYmxlKCkge1xuICAgICAgICAkKHRoaXMpLnBhcmVudCgpLmhpZGUoKTtcbiAgICAgIH0pO1xuICAgIH0pO1xuXG4gICQoZG9jdW1lbnQpLnJlYWR5KCgpID0+IHtcbiAgICAvLyBEaXNhYmxlIGNvcHkvcGFzdGUgYWN0aW9ucyBvbiBiaWxsaW5nIGVtYWlsIGZpZWxkcy5cbiAgICBpZiAoc2hvcF9zdGFuZGFyZHNfc2V0dGluZ3MuZW1haWxDb25maXJtYXRpb25FbWFpbCA9PT0gJ3llcycpIHtcbiAgICAgICQoJyNiaWxsaW5nX2VtYWlsLCAjYmlsbGluZ19lbWFpbF9jb25maXJtYXRpb24nKS5vbignY3V0IGNvcHkgcGFzdGUnLCAoZSkgPT4ge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICB9KTtcblxuICAgICAgLy8gTWFya3MgZW1haWwgY29uZmlybWF0aW9uIGZpZWxkIGFzIGludmFsaWQgaWYgZG9lcyBub3QgbWF0Y2ggZW1haWxcbiAgICAgIC8vIGZpZWxkIG9uIGZvcm0gc3VibWl0LlxuICAgICAgJCgnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcpLm9uKCdpbnB1dCB2YWxpZGF0ZSBjaGFuZ2UnLCAoKSA9PiB7XG4gICAgICAgIGlmICgkKCcjYmlsbGluZ19lbWFpbCcpLnZhbCgpICE9PSAkKCcjYmlsbGluZ19lbWFpbF9jb25maXJtYXRpb24nKS52YWwoKSkge1xuICAgICAgICAgICQoJyNiaWxsaW5nX2VtYWlsX2NvbmZpcm1hdGlvbl9maWVsZCcpLnJlbW92ZUNsYXNzKCd3b29jb21tZXJjZS12YWxpZGF0ZWQnKS5hZGRDbGFzcygnd29vY29tbWVyY2UtaW52YWxpZCB3b29jb21tZXJjZS1pbnZhbGlkLXJlcXVpcmVkLWZpZWxkJyk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH1cbiAgfSk7XG5cbiAgLy8gRGlzYWJsZSBjaGVja291dCBidXR0b24gaWYgY2FydCBvbmx5IGNvbnRhaW5zIGxvdyByZXR1cm4gcHJvZHVjdHMuXG4gICQoZG9jdW1lbnQpLm9uKCdyZWFkeSB1cGRhdGVkX2NhcnRfdG90YWxzJywgKCkgPT4ge1xuICAgIGlmICgkKCcud29vY29tbWVyY2UtZXJyb3IgbGlbZGF0YS1wbHVzLXByb2R1Y3Q9XCJpbnZhbGlkXCJdJykubGVuZ3RoKSB7XG4gICAgICAkKCcuY2hlY2tvdXQtYnV0dG9uJykucmVtb3ZlQXR0cignaHJlZicpLmFkZENsYXNzKCdkaXNhYmxlZCcpO1xuICAgICAgJCgnLndjcHBlYy1jaGVja291dC1idXR0b25zJykuaGlkZSgpO1xuICAgIH1cbiAgfSk7XG5cbiAgLy8gVXNlZCBhbmQgZGVmZWN0aXZlIGdvb2RzIGNoZWNrYm94IGFncmVlbWVudCByZWxhdGVkIGZ1bmN0aW9uYWxpdHkuXG4gICQoZG9jdW1lbnQpLnJlYWR5KCgpID0+IHtcbiAgICBjb25zdCAkdXNlZEdvb2RzQ29uc2VudENoZWNrYm94ID0gJCgnI3VzZWQtZ29vZHMtY29uc2VudCcpO1xuICAgIGNvbnN0ICR1c2VkR29vZHNDb25zZW50Q29udGFpbmVyID0gJCgnLnByb2R1Y3QtZGVmZWN0c19fY2hlY2tib3gtY29udGFpbmVyJyk7XG4gICAgaWYgKCR1c2VkR29vZHNDb25zZW50Q2hlY2tib3gubGVuZ3RoKSB7XG4gICAgICAkKGRvY3VtZW50KVxuICAgICAgICAub24oJ3Nob3dfdmFyaWF0aW9uJywgJy5zaW5nbGVfdmFyaWF0aW9uX3dyYXAnLCAoZXZlbnQsIHZhcmlhdGlvbikgPT4ge1xuICAgICAgICAgIGNvbnN0IHVzZWRHb29kc0NvbnNlbnRBdHRyID0gdmFyaWF0aW9uLnVzZWRfZ29vZHNfY29uc2VudF9hdHRyaWJ1dGU7XG4gICAgICAgICAgJCgnLnByb2R1Y3QtZGVmZWN0c19fYXR0cmlidXRlJykudGV4dCh1c2VkR29vZHNDb25zZW50QXR0cik7XG4gICAgICAgICAgaWYgKHVzZWRHb29kc0NvbnNlbnRBdHRyKSB7XG4gICAgICAgICAgICAkdXNlZEdvb2RzQ29uc2VudENvbnRhaW5lci5zaG93KCk7XG4gICAgICAgICAgICAkdXNlZEdvb2RzQ29uc2VudENoZWNrYm94LnByb3AoJ3JlcXVpcmVkJywgJ3JlcXVpcmVkJyk7XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICR1c2VkR29vZHNDb25zZW50Q29udGFpbmVyLmhpZGUoKTtcbiAgICAgICAgICAgICR1c2VkR29vZHNDb25zZW50Q2hlY2tib3gucmVtb3ZlQXR0cigncmVxdWlyZWQnKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cbiAgfSk7XG59KGpRdWVyeSkpO1xuIl0sImZpbGUiOiJtYWluLmpzIn0=