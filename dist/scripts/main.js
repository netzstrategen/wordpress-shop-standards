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
  }); // Disable checkout button if there are any WooCommerce error displayed.

  $(document).on('ready updated_cart_totals', function () {
    if ($('.woocommerce-error').length) {
      $('.checkout-button').removeAttr('href').addClass('disabled');
      $('.wcppec-checkout-buttons').hide();
    }
  }); // Used and defective goods checkbox agreement related functionality.

  $(document).ready(function () {
    var $checkboxEl = $('#used-goods-consent');
    console.log($checkboxEl);

    if (!$checkboxEl.length) {
      return;
    }

    var $addToCartButton = $('.cart');
    console.log($addToCartButton);
    $addToCartButton.prop('disabled', 'disabled'); // $(document)
    //   .on('show_variation', '.single_variation_wrap', (event, variation) => {
    //     const zustand = variation.attribute_pa_zustand_name;
    //     $checkboxElem.detach();
    //     const placeholder = $('.product_meta');
    //     if (zustand !== 'Originalverpackte Neuware') {
    //       $checkboxElem.insertAfter(placeholder);
    //       $('.zustand').text(zustand);
    //     }
    //   });
  });
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOlsicGFnZUxvYWQiLCIkIiwib24iLCJlIiwidXJsIiwidGFyZ2V0IiwiZGF0YSIsImNsb3Nlc3QiLCJkb2N1bWVudCIsImxvY2F0aW9uIiwiaHJlZiIsInBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUiLCJwcm9wIiwiY2xpY2siLCJkaXNhYmxlU3VibWl0T3JkZXIiLCJoYXNDbGFzcyIsInByZXZlbnREZWZhdWx0IiwiYWRkQ2xhc3MiLCJib2R5IiwicmVtb3ZlQ2xhc3MiLCIkdmFyaWF0aW9uc0Zvcm0iLCIkdmFyaWF0aW9uc1NlbGVjdERyb3Bkb3ducyIsIiR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkIiwiY2hhbmdlIiwidmFyaWF0aW9uU2VsZWN0RHJvcGRvd25DaGFuZ2VkIiwidmFsIiwiZWFjaCIsInNldFZhcmlhdGlvblNlbGVjdERyb3Bkb3ducyIsIiR0aGlzIiwiZmluZCIsInNpemUiLCJlcSIsInNldFRpbWVvdXQiLCJ0cmlnZ2VyIiwiZXZlbnQiLCJ2YXJpYXRpb24iLCJwYXJlbnQiLCJoaWRlIiwidXBkYXRlRGlzY291bnRUYWJsZSIsImluZGV4T2YiLCJ2YXJpYXRpb25faWQiLCJ0b1N0cmluZyIsInNob3ciLCJoaWRlRGlzY291bnRUYWJsZSIsInJlYWR5Iiwic2hvcF9zdGFuZGFyZHNfc2V0dGluZ3MiLCJlbWFpbENvbmZpcm1hdGlvbkVtYWlsIiwibGVuZ3RoIiwicmVtb3ZlQXR0ciIsIiRjaGVja2JveEVsIiwiY29uc29sZSIsImxvZyIsIiRhZGRUb0NhcnRCdXR0b24iLCJqUXVlcnkiXSwibWFwcGluZ3MiOiJBQUFBO0FBRUMsVUFBU0EsUUFBVCxDQUFrQkMsQ0FBbEIsRUFBcUI7QUFDcEI7QUFDQUEsRUFBQUEsQ0FBQyxDQUFDLE1BQUQsQ0FBRCxDQUNHQyxFQURILENBRUksT0FGSixFQUdJLFlBSEosRUFJSSxVQUFDQyxDQUFELEVBQU87QUFDTCxRQUFNQyxHQUFHLEdBQUdILENBQUMsQ0FBQ0UsQ0FB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QUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNELEdBbkJEO0FBb0JELENBaklBLEVBaUlDeUMsTUFqSUQsQ0FBRCIsInNvdXJjZXNDb250ZW50IjpbIi8qIGdsb2JhbCBqUXVlcnksIHNob3Bfc3RhbmRhcmRzX3NldHRpbmdzICovXG5cbihmdW5jdGlvbiBwYWdlTG9hZCgkKSB7XG4gIC8vIFRvZ2dsZXMgcHJvZHVjdCBmaWx0ZXJpbmcgYnkgdGVybS5cbiAgJCgnYm9keScpXG4gICAgLm9uKFxuICAgICAgJ2NsaWNrJyxcbiAgICAgICdbZGF0YS11cmxdJyxcbiAgICAgIChlKSA9PiB7XG4gICAgICAgIGNvbnN0IHVybCA9ICQoZS50YXJnZXQpLmRhdGEoJ3VybCcpIHx8ICQoZS50YXJnZXQpLmNsb3Nlc3QoJ1tkYXRhLXVybF0nKS5kYXRhKCd1cmwnKTtcbiAgICAgICAgaWYgKHVybCkge1xuICAgICAgICAgIGRvY3VtZW50LmxvY2F0aW9uLmhyZWYgPSB1cmw7XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgKTtcblxuICAvKipcbiAgICogUHJldmVudHMgbXVsdGlwbGUgb3JkZXIgdG8gYmUgc2VudC5cbiAgICpcbiAgICogSWYgdGhlIHBhZ2UgdGFrZXMgdGltZSB0byBiZSBsb2FkZWQsIHRoZSB1c2VyIGNvdWxkIGNsaWNrIG11bHRpcGxlXG4gICAqIHRpbWVzIG9uIHRoZSBwbGFjZSBvcmRlciBidXR0b24gYW5kIHRoaXMgd291bGQgZ2VuZXJhdGUgbXVsdGlwbGUgb3JkZXJzLlxuICAgKiBUbyBwcmV2ZW50IHRoaXMgd2Ugc3RvcCBmb3JtIHN1Ym1pdCBldmVudCBwcm9wYWdhdGlvbiBhZnRlciB0aGUgZmlyc3QgY2xpY2suXG4gICAqL1xuXG4gIGNvbnN0IHBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUgPSAkKCcjcGxhY2Vfb3JkZXInKS5wcm9wKCd2YWx1ZScpO1xuICAkKCcjcGxhY2Vfb3JkZXInKS5jbGljayhmdW5jdGlvbiBkaXNhYmxlU3VibWl0T3JkZXIoZSkge1xuICAgIGlmICgkKHRoaXMpLmhhc0NsYXNzKCdkaXNhYmxlZCcpKSB7XG4gICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICByZXR1cm47XG4gICAgfVxuICAgICQoJyNwbGFjZV9vcmRlcicpLnByb3AoJ3ZhbHVlJywgJ0locmUgQmVzdGVsbHVuZyB3aXJkIGpldHp0IHZlcmFyYmVpdGV04oCmJykuYWRkQ2xhc3MoJ2Rpc2FibGVkJyk7XG4gIH0pO1xuICAkKGRvY3VtZW50LmJvZHkpLm9uKCdjaGVja291dF9lcnJvcicsICgpID0+IHtcbiAgICAkKCcjcGxhY2Vfb3JkZXInKS5wcm9wKCd2YWx1ZScsIHBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUpLnJlbW92ZUNsYXNzKCdkaXNhYmxlZCcpO1xuICB9KTtcblxuICBjb25zdCAkdmFyaWF0aW9uc0Zvcm0gPSAkKCcudmFyaWF0aW9uc19mb3JtJyk7XG4gIGNvbnN0ICR2YXJpYXRpb25zU2VsZWN0RHJvcGRvd25zID0gJCgnLnZhcmlhdGlvbnNfZm9ybSAudmFyaWF0aW9ucyBzZWxlY3QnKTtcbiAgbGV0ICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkID0gZmFsc2U7XG5cbiAgJHZhcmlhdGlvbnNTZWxlY3REcm9wZG93bnMuY2hhbmdlKGZ1bmN0aW9uIHZhcmlhdGlvblNlbGVjdERyb3Bkb3duQ2hhbmdlZCgpIHtcbiAgICAkdmFyaWF0aW9uU2VsZWN0Q2hhbmdlZCA9ICQodGhpcyk7XG4gIH0pO1xuXG4gICQoJy52YXJpYXRpb25zX2Zvcm0nKS5vbignd29vY29tbWVyY2VfdmFyaWF0aW9uX2hhc19jaGFuZ2VkJywgKCkgPT4ge1xuICAgIC8vIEFsbG93IHNlbGVjdGluZyB0aGUgZGVmYXVsdCBlbXB0eSB2YWx1ZSBvZiBhbiBhdHRyaWJ1dGVzIGRyb3Bkb3duXG4gICAgLy8gd2l0aG91dCBtb2RpZnlpbmcgdGhlIHZhbHVlIG9mIHRoZSBvdGhlcnMuXG4gICAgaWYgKCR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkICYmICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkLnZhbCgpID09PSAnJykge1xuICAgICAgJHZhcmlhdGlvblNlbGVjdENoYW5nZWQudmFsKCcnKTtcbiAgICAgICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkID0gZmFsc2U7XG4gICAgfSBlbHNlIHtcbiAgICAgIC8vIElmIHRoZXJlIGlzIG9ubHkgb25lIG9wdGlvbiBsZWZ0IGluIGFueSBvZiBjdXJyZW50IHZhcmlhdGlvbiBhdHRyaWJ1dGVzXG4gICAgICAvLyBkcm9wZG93bnMsIGl0IHNob3VsZCBiZSBhdXRvLXNlbGVjdGVkLlxuICAgICAgJHZhcmlhdGlvbnNTZWxlY3REcm9wZG93bnMuZWFjaChmdW5jdGlvbiBzZXRWYXJpYXRpb25TZWxlY3REcm9wZG93bnMoKSB7XG4gICAgICAgIGNvbnN0ICR0aGlzID0gJCh0aGlzKTtcbiAgICAgICAgaWYgKCR0aGlzLmZpbmQoJ29wdGlvbicpLnNpemUoKSA9PT0gMikge1xuICAgICAgICAgICR0aGlzLnZhbCgkdGhpcy5maW5kKCdvcHRpb24nKS5lcSgxKS52YWwoKSk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH1cbiAgICAvLyBFbnN1cmUgdGhlIHJpZ3RoIHByb2R1Y3QgaW1hZ2UgaXMgZGlzcGxheWVkLlxuICAgIC8vIFNvbWUgZGVsYXkgc2VlbXMgdG8gYmUgbmVlZGVkIHRvIHJlZnJlc2ggdGhlIHByb2R1Y3QgaW1hZ2UuXG4gICAgLy8gV2UgY291bGRuJ3QgZmluZCBhIHByb3BlciBldmVudCB0byBob29rIG9uLCBzbyB3ZSB1c2VkIGEgdGltZW91dC5cbiAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICR2YXJpYXRpb25zRm9ybS50cmlnZ2VyKCdjaGVja192YXJpYXRpb25zJyk7XG4gICAgfSwgMTAwKTtcbiAgfSk7XG5cbiAgJCgnLnNpbmdsZV92YXJpYXRpb25fd3JhcCcpXG4gICAgLm9uKCdzaG93X3ZhcmlhdGlvbicsIChldmVudCwgdmFyaWF0aW9uKSA9PiB7XG4gICAgICAvLyBVcGRhdGVzIGRpc2NvdW50IHRhYmxlIG9uIHByb2R1Y3QgdmFyaWF0aW9uIGNoYW5nZS5cbiAgICAgICQoJ1tkYXRhLXZhcmlhdGlvbnNdJykucGFyZW50KCkuaGlkZSgpO1xuICAgICAgJCgkKCdbZGF0YS12YXJpYXRpb25zXScpKS5lYWNoKGZ1bmN0aW9uIHVwZGF0ZURpc2NvdW50VGFibGUoKSB7XG4gICAgICAgIGlmICgkKHRoaXMpLmRhdGEoJ3ZhcmlhdGlvbnMnKS5pbmRleE9mKHZhcmlhdGlvbi52YXJpYXRpb25faWQudG9TdHJpbmcoKSkgIT09IC0xKSB7XG4gICAgICAgICAgJCh0aGlzKS5wYXJlbnQoKS5zaG93KCk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH0pXG4gICAgLm9uKCdoaWRlX3ZhcmlhdGlvbicsICgpID0+IHtcbiAgICAgIC8vIEhpZGVzIGFsbCB2YXJpYXRpb24gcHJvZHVjdCBkaXNjb3VudCB0YWJsZSBvbiBwcm9kdWN0IHZhcmlhdGlvbiBoaWRlLlxuICAgICAgJCgkKCdbZGF0YS12YXJpYXRpb25zXScpKS5lYWNoKGZ1bmN0aW9uIGhpZGVEaXNjb3VudFRhYmxlKCkge1xuICAgICAgICAkKHRoaXMpLnBhcmVudCgpLmhpZGUoKTtcbiAgICAgIH0pO1xuICAgIH0pO1xuXG4gICQoZG9jdW1lbnQpLnJlYWR5KCgpID0+IHtcbiAgICAvLyBEaXNhYmxlIGNvcHkvcGFzdGUgYWN0aW9ucyBvbiBiaWxsaW5nIGVtYWlsIGZpZWxkcy5cbiAgICBpZiAoc2hvcF9zdGFuZGFyZHNfc2V0dGluZ3MuZW1haWxDb25maXJtYXRpb25FbWFpbCA9PT0gJ3llcycpIHtcbiAgICAgICQoJyNiaWxsaW5nX2VtYWlsLCAjYmlsbGluZ19lbWFpbF9jb25maXJtYXRpb24nKS5vbignY3V0IGNvcHkgcGFzdGUnLCAoZSkgPT4ge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICB9KTtcblxuICAgICAgLy8gTWFya3MgZW1haWwgY29uZmlybWF0aW9uIGZpZWxkIGFzIGludmFsaWQgaWYgZG9lcyBub3QgbWF0Y2ggZW1haWxcbiAgICAgIC8vIGZpZWxkIG9uIGZvcm0gc3VibWl0LlxuICAgICAgJCgnZm9ybS53b29jb21tZXJjZS1jaGVja291dCcpLm9uKCdpbnB1dCB2YWxpZGF0ZSBjaGFuZ2UnLCAoKSA9PiB7XG4gICAgICAgIGlmICgkKCcjYmlsbGluZ19lbWFpbCcpLnZhbCgpICE9PSAkKCcjYmlsbGluZ19lbWFpbF9jb25maXJtYXRpb24nKS52YWwoKSkge1xuICAgICAgICAgICQoJyNiaWxsaW5nX2VtYWlsX2NvbmZpcm1hdGlvbl9maWVsZCcpLnJlbW92ZUNsYXNzKCd3b29jb21tZXJjZS12YWxpZGF0ZWQnKS5hZGRDbGFzcygnd29vY29tbWVyY2UtaW52YWxpZCB3b29jb21tZXJjZS1pbnZhbGlkLXJlcXVpcmVkLWZpZWxkJyk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH1cbiAgfSk7XG5cbiAgLy8gRGlzYWJsZSBjaGVja291dCBidXR0b24gaWYgdGhlcmUgYXJlIGFueSBXb29Db21tZXJjZSBlcnJvciBkaXNwbGF5ZWQuXG4gICQoZG9jdW1lbnQpLm9uKCdyZWFkeSB1cGRhdGVkX2NhcnRfdG90YWxzJywgKCkgPT4ge1xuICAgIGlmICgkKCcud29vY29tbWVyY2UtZXJyb3InKS5sZW5ndGgpIHtcbiAgICAgICQoJy5jaGVja291dC1idXR0b24nKS5yZW1vdmVBdHRyKCdocmVmJykuYWRkQ2xhc3MoJ2Rpc2FibGVkJyk7XG4gICAgICAkKCcud2NwcGVjLWNoZWNrb3V0LWJ1dHRvbnMnKS5oaWRlKCk7XG4gICAgfVxuICB9KTtcblxuICAvLyBVc2VkIGFuZCBkZWZlY3RpdmUgZ29vZHMgY2hlY2tib3ggYWdyZWVtZW50IHJlbGF0ZWQgZnVuY3Rpb25hbGl0eS5cbiAgJChkb2N1bWVudCkucmVhZHkoKCkgPT4ge1xuICAgIGNvbnN0ICRjaGVja2JveEVsID0gJCgnI3VzZWQtZ29vZHMtY29uc2VudCcpO1xuICAgIGNvbnNvbGUubG9nKCRjaGVja2JveEVsKTtcbiAgICBpZiAoISRjaGVja2JveEVsLmxlbmd0aCkge1xuICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBjb25zdCAkYWRkVG9DYXJ0QnV0dG9uID0gJCgnLmNhcnQnKTtcbiAgICBjb25zb2xlLmxvZygkYWRkVG9DYXJ0QnV0dG9uKTtcbiAgICAkYWRkVG9DYXJ0QnV0dG9uLnByb3AoJ2Rpc2FibGVkJywgJ2Rpc2FibGVkJyk7XG4gICAgLy8gJChkb2N1bWVudClcbiAgICAvLyAgIC5vbignc2hvd192YXJpYXRpb24nLCAnLnNpbmdsZV92YXJpYXRpb25fd3JhcCcsIChldmVudCwgdmFyaWF0aW9uKSA9PiB7XG4gICAgLy8gICAgIGNvbnN0IHp1c3RhbmQgPSB2YXJpYXRpb24uYXR0cmlidXRlX3BhX3p1c3RhbmRfbmFtZTtcbiAgICAvLyAgICAgJGNoZWNrYm94RWxlbS5kZXRhY2goKTtcbiAgICAvLyAgICAgY29uc3QgcGxhY2Vob2xkZXIgPSAkKCcucHJvZHVjdF9tZXRhJyk7XG4gICAgLy8gICAgIGlmICh6dXN0YW5kICE9PSAnT3JpZ2luYWx2ZXJwYWNrdGUgTmV1d2FyZScpIHtcbiAgICAvLyAgICAgICAkY2hlY2tib3hFbGVtLmluc2VydEFmdGVyKHBsYWNlaG9sZGVyKTtcbiAgICAvLyAgICAgICAkKCcuenVzdGFuZCcpLnRleHQoenVzdGFuZCk7XG4gICAgLy8gICAgIH1cbiAgICAvLyAgIH0pO1xuICB9KTtcbn0oalF1ZXJ5KSk7XG4iXSwiZmlsZSI6Im1haW4uanMifQ==