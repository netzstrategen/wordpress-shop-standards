/* global jQuery, shop_standards_settings */
(function pageLoad($) {
  // Toggles product filtering by term.
  $('body').on('click', '[data-url]:not(input[name^="docs-"]))', function (e) {
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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm1haW4uanMiXSwibmFtZXMiOlsicGFnZUxvYWQiLCIkIiwib24iLCJlIiwidXJsIiwidGFyZ2V0IiwiZGF0YSIsImNsb3Nlc3QiLCJkb2N1bWVudCIsImxvY2F0aW9uIiwiaHJlZiIsInBsYWNlT3JkZXJCdG5Jbml0aWFsVmFsdWUiLCJwcm9wIiwiY2xpY2siLCJkaXNhYmxlU3VibWl0T3JkZXIiLCJoYXNDbGFzcyIsInByZXZlbnREZWZhdWx0IiwiYWRkQ2xhc3MiLCJib2R5IiwicmVtb3ZlQ2xhc3MiLCIkdmFyaWF0aW9uc0Zvcm0iLCIkdmFyaWF0aW9uc1NlbGVjdERyb3Bkb3ducyIsIiR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkIiwiY2hhbmdlIiwidmFyaWF0aW9uU2VsZWN0RHJvcGRvd25DaGFuZ2VkIiwidmFsIiwiZWFjaCIsInNldFZhcmlhdGlvblNlbGVjdERyb3Bkb3ducyIsIiR0aGlzIiwiZmluZCIsInNpemUiLCJlcSIsInNldFRpbWVvdXQiLCJ0cmlnZ2VyIiwiZXZlbnQiLCJ2YXJpYXRpb24iLCJwYXJlbnQiLCJoaWRlIiwidXBkYXRlRGlzY291bnRUYWJsZSIsImluZGV4T2YiLCJ2YXJpYXRpb25faWQiLCJ0b1N0cmluZyIsInNob3ciLCJoaWRlRGlzY291bnRUYWJsZSIsInJlYWR5Iiwic2hvcF9zdGFuZGFyZHNfc2V0dGluZ3MiLCJlbWFpbENvbmZpcm1hdGlvbkVtYWlsIiwibGVuZ3RoIiwicmVtb3ZlQXR0ciIsIiR1c2VkR29vZHNDb25zZW50Q2hlY2tib3giLCIkdXNlZEdvb2RzQ29uc2VudENvbnRhaW5lciIsInVzZWRHb29kc0NvbnNlbnRBdHRyIiwidXNlZF9nb29kc19jb25zZW50X2F0dHJpYnV0ZSIsInRleHQiLCJqUXVlcnkiXSwibWFwcGluZ3MiOiJBQUFBO0FBRUMsVUFBU0EsUUFBVCxDQUFrQkMsQ0FBbEIsRUFBcUI7QUFDcEI7QUFDQUEsRUFBQUEsQ0FBQyxDQUFDLE1BQUQsQ0FBRCxDQUNHQyxFQURILENBRUksT0FG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QUNwRSxZQUFNZ0Isb0JBQW9CLEdBQUdoQixTQUFTLENBQUNpQiw0QkFBdkM7QUFDQW5ELFFBQUFBLENBQUMsQ0FBQyw2QkFBRCxDQUFELENBQWlDb0QsSUFBakMsQ0FBc0NGLG9CQUF0Qzs7QUFDQSxZQUFJQSxvQkFBSixFQUEwQjtBQUN4QkQsVUFBQUEsMEJBQTBCLENBQUNSLElBQTNCO0FBQ0FPLFVBQUFBLHlCQUF5QixDQUFDckMsSUFBMUIsQ0FBK0IsVUFBL0IsRUFBMkMsVUFBM0M7QUFDRCxTQUhELE1BR087QUFDTHNDLFVBQUFBLDBCQUEwQixDQUFDYixJQUEzQjtBQUNBWSxVQUFBQSx5QkFBeUIsQ0FBQ0QsVUFBMUIsQ0FBcUMsVUFBckM7QUFDRDtBQUNGLE9BWEg7QUFZRDtBQUNGLEdBakJEO0FBa0JELENBL0hBLEVBK0hDTSxNQS9IRCxDQUFEIiwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIGpRdWVyeSwgc2hvcF9zdGFuZGFyZHNfc2V0dGluZ3MgKi9cblxuKGZ1bmN0aW9uIHBhZ2VMb2FkKCQpIHtcbiAgLy8gVG9nZ2xlcyBwcm9kdWN0IGZpbHRlcmluZyBieSB0ZXJtLlxuICAkKCdib2R5JylcbiAgICAub24oXG4gICAgICAnY2xpY2snLFxuICAgICAgJ1tkYXRhLXVybF0nLFxuICAgICAgKGUpID0+IHtcbiAgICAgICAgY29uc3QgdXJsID0gJChlLnRhcmdldCkuZGF0YSgndXJsJykgfHwgJChlLnRhcmdldCkuY2xvc2VzdCgnW2RhdGEtdXJsXScpLmRhdGEoJ3VybCcpO1xuICAgICAgICBpZiAodXJsKSB7XG4gICAgICAgICAgZG9jdW1lbnQubG9jYXRpb24uaHJlZiA9IHVybDtcbiAgICAgICAgfVxuICAgICAgfSxcbiAgICApO1xuXG4gIC8qKlxuICAgKiBQcmV2ZW50cyBtdWx0aXBsZSBvcmRlciB0byBiZSBzZW50LlxuICAgKlxuICAgKiBJZiB0aGUgcGFnZSB0YWtlcyB0aW1lIHRvIGJlIGxvYWRlZCwgdGhlIHVzZXIgY291bGQgY2xpY2sgbXVsdGlwbGVcbiAgICogdGltZXMgb24gdGhlIHBsYWNlIG9yZGVyIGJ1dHRvbiBhbmQgdGhpcyB3b3VsZCBnZW5lcmF0ZSBtdWx0aXBsZSBvcmRlcnMuXG4gICAqIFRvIHByZXZlbnQgdGhpcyB3ZSBzdG9wIGZvcm0gc3VibWl0IGV2ZW50IHByb3BhZ2F0aW9uIGFmdGVyIHRoZSBmaXJzdCBjbGljay5cbiAgICovXG5cbiAgY29uc3QgcGxhY2VPcmRlckJ0bkluaXRpYWxWYWx1ZSA9ICQoJyNwbGFjZV9vcmRlcicpLnByb3AoJ3ZhbHVlJyk7XG4gICQoJyNwbGFjZV9vcmRlcicpLmNsaWNrKGZ1bmN0aW9uIGRpc2FibGVTdWJtaXRPcmRlcihlKSB7XG4gICAgaWYgKCQodGhpcykuaGFzQ2xhc3MoJ2Rpc2FibGVkJykpIHtcbiAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG4gICAgJCgnI3BsYWNlX29yZGVyJykucHJvcCgndmFsdWUnLCAnSWhyZSBCZXN0ZWxsdW5nIHdpcmQgamV0enQgdmVyYXJiZWl0ZXTigKYnKS5hZGRDbGFzcygnZGlzYWJsZWQnKTtcbiAgfSk7XG4gICQoZG9jdW1lbnQuYm9keSkub24oJ2NoZWNrb3V0X2Vycm9yJywgKCkgPT4ge1xuICAgICQoJyNwbGFjZV9vcmRlcicpLnByb3AoJ3ZhbHVlJywgcGxhY2VPcmRlckJ0bkluaXRpYWxWYWx1ZSkucmVtb3ZlQ2xhc3MoJ2Rpc2FibGVkJyk7XG4gIH0pO1xuXG4gIGNvbnN0ICR2YXJpYXRpb25zRm9ybSA9ICQoJy52YXJpYXRpb25zX2Zvcm0nKTtcbiAgY29uc3QgJHZhcmlhdGlvbnNTZWxlY3REcm9wZG93bnMgPSAkKCcudmFyaWF0aW9uc19mb3JtIC52YXJpYXRpb25zIHNlbGVjdCcpO1xuICBsZXQgJHZhcmlhdGlvblNlbGVjdENoYW5nZWQgPSBmYWxzZTtcblxuICAkdmFyaWF0aW9uc1NlbGVjdERyb3Bkb3ducy5jaGFuZ2UoZnVuY3Rpb24gdmFyaWF0aW9uU2VsZWN0RHJvcGRvd25DaGFuZ2VkKCkge1xuICAgICR2YXJpYXRpb25TZWxlY3RDaGFuZ2VkID0gJCh0aGlzKTtcbiAgfSk7XG5cbiAgJCgnLnZhcmlhdGlvbnNfZm9ybScpLm9uKCd3b29jb21tZXJjZV92YXJpYXRpb25faGFzX2NoYW5nZWQnLCAoKSA9PiB7XG4gICAgLy8gQWxsb3cgc2VsZWN0aW5nIHRoZSBkZWZhdWx0IGVtcHR5IHZhbHVlIG9mIGFuIGF0dHJpYnV0ZXMgZHJvcGRvd25cbiAgICAvLyB3aXRob3V0IG1vZGlmeWluZyB0aGUgdmFsdWUgb2YgdGhlIG90aGVycy5cbiAgICBpZiAoJHZhcmlhdGlvblNlbGVjdENoYW5nZWQgJiYgJHZhcmlhdGlvblNlbGVjdENoYW5nZWQudmFsKCkgPT09ICcnKSB7XG4gICAgICAkdmFyaWF0aW9uU2VsZWN0Q2hhbmdlZC52YWwoJycpO1xuICAgICAgJHZhcmlhdGlvblNlbGVjdENoYW5nZWQgPSBmYWxzZTtcbiAgICB9IGVsc2Uge1xuICAgICAgLy8gSWYgdGhlcmUgaXMgb25seSBvbmUgb3B0aW9uIGxlZnQgaW4gYW55IG9mIGN1cnJlbnQgdmFyaWF0aW9uIGF0dHJpYnV0ZXNcbiAgICAgIC8vIGRyb3Bkb3ducywgaXQgc2hvdWxkIGJlIGF1dG8tc2VsZWN0ZWQuXG4gICAgICAkdmFyaWF0aW9uc1NlbGVjdERyb3Bkb3ducy5lYWNoKGZ1bmN0aW9uIHNldFZhcmlhdGlvblNlbGVjdERyb3Bkb3ducygpIHtcbiAgICAgICAgY29uc3QgJHRoaXMgPSAkKHRoaXMpO1xuICAgICAgICBpZiAoJHRoaXMuZmluZCgnb3B0aW9uJykuc2l6ZSgpID09PSAyKSB7XG4gICAgICAgICAgJHRoaXMudmFsKCR0aGlzLmZpbmQoJ29wdGlvbicpLmVxKDEpLnZhbCgpKTtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgfVxuICAgIC8vIEVuc3VyZSB0aGUgcmlndGggcHJvZHVjdCBpbWFnZSBpcyBkaXNwbGF5ZWQuXG4gICAgLy8gU29tZSBkZWxheSBzZWVtcyB0byBiZSBuZWVkZWQgdG8gcmVmcmVzaCB0aGUgcHJvZHVjdCBpbWFnZS5cbiAgICAvLyBXZSBjb3VsZG4ndCBmaW5kIGEgcHJvcGVyIGV2ZW50IHRvIGhvb2sgb24sIHNvIHdlIHVzZWQgYSB0aW1lb3V0LlxuICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgJHZhcmlhdGlvbnNGb3JtLnRyaWdnZXIoJ2NoZWNrX3ZhcmlhdGlvbnMnKTtcbiAgICB9LCAxMDApO1xuICB9KTtcblxuICAkKCcuc2luZ2xlX3ZhcmlhdGlvbl93cmFwJylcbiAgICAub24oJ3Nob3dfdmFyaWF0aW9uJywgKGV2ZW50LCB2YXJpYXRpb24pID0+IHtcbiAgICAgIC8vIFVwZGF0ZXMgZGlzY291bnQgdGFibGUgb24gcHJvZHVjdCB2YXJpYXRpb24gY2hhbmdlLlxuICAgICAgJCgnW2RhdGEtdmFyaWF0aW9uc10nKS5wYXJlbnQoKS5oaWRlKCk7XG4gICAgICAkKCQoJ1tkYXRhLXZhcmlhdGlvbnNdJykpLmVhY2goZnVuY3Rpb24gdXBkYXRlRGlzY291bnRUYWJsZSgpIHtcbiAgICAgICAgaWYgKCQodGhpcykuZGF0YSgndmFyaWF0aW9ucycpLmluZGV4T2YodmFyaWF0aW9uLnZhcmlhdGlvbl9pZC50b1N0cmluZygpKSAhPT0gLTEpIHtcbiAgICAgICAgICAkKHRoaXMpLnBhcmVudCgpLnNob3coKTtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgfSlcbiAgICAub24oJ2hpZGVfdmFyaWF0aW9uJywgKCkgPT4ge1xuICAgICAgLy8gSGlkZXMgYWxsIHZhcmlhdGlvbiBwcm9kdWN0IGRpc2NvdW50IHRhYmxlIG9uIHByb2R1Y3QgdmFyaWF0aW9uIGhpZGUuXG4gICAgICAkKCQoJ1tkYXRhLXZhcmlhdGlvbnNdJykpLmVhY2goZnVuY3Rpb24gaGlkZURpc2NvdW50VGFibGUoKSB7XG4gICAgICAgICQodGhpcykucGFyZW50KCkuaGlkZSgpO1xuICAgICAgfSk7XG4gICAgfSk7XG5cbiAgJChkb2N1bWVudCkucmVhZHkoKCkgPT4ge1xuICAgIC8vIERpc2FibGUgY29weS9wYXN0ZSBhY3Rpb25zIG9uIGJpbGxpbmcgZW1haWwgZmllbGRzLlxuICAgIGlmIChzaG9wX3N0YW5kYXJkc19zZXR0aW5ncy5lbWFpbENvbmZpcm1hdGlvbkVtYWlsID09PSAneWVzJykge1xuICAgICAgJCgnI2JpbGxpbmdfZW1haWwsICNiaWxsaW5nX2VtYWlsX2NvbmZpcm1hdGlvbicpLm9uKCdjdXQgY29weSBwYXN0ZScsIChlKSA9PiB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgIH0pO1xuXG4gICAgICAvLyBNYXJrcyBlbWFpbCBjb25maXJtYXRpb24gZmllbGQgYXMgaW52YWxpZCBpZiBkb2VzIG5vdCBtYXRjaCBlbWFpbFxuICAgICAgLy8gZmllbGQgb24gZm9ybSBzdWJtaXQuXG4gICAgICAkKCdmb3JtLndvb2NvbW1lcmNlLWNoZWNrb3V0Jykub24oJ2lucHV0IHZhbGlkYXRlIGNoYW5nZScsICgpID0+IHtcbiAgICAgICAgaWYgKCQoJyNiaWxsaW5nX2VtYWlsJykudmFsKCkgIT09ICQoJyNiaWxsaW5nX2VtYWlsX2NvbmZpcm1hdGlvbicpLnZhbCgpKSB7XG4gICAgICAgICAgJCgnI2JpbGxpbmdfZW1haWxfY29uZmlybWF0aW9uX2ZpZWxkJykucmVtb3ZlQ2xhc3MoJ3dvb2NvbW1lcmNlLXZhbGlkYXRlZCcpLmFkZENsYXNzKCd3b29jb21tZXJjZS1pbnZhbGlkIHdvb2NvbW1lcmNlLWludmFsaWQtcmVxdWlyZWQtZmllbGQnKTtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgfVxuICB9KTtcblxuICAvLyBEaXNhYmxlIGNoZWNrb3V0IGJ1dHRvbiBpZiBjYXJ0IG9ubHkgY29udGFpbnMgbG93IHJldHVybiBwcm9kdWN0cy5cbiAgJChkb2N1bWVudCkub24oJ3JlYWR5IHVwZGF0ZWRfY2FydF90b3RhbHMnLCAoKSA9PiB7XG4gICAgaWYgKCQoJy53b29jb21tZXJjZS1lcnJvciBsaVtkYXRhLXBsdXMtcHJvZHVjdD1cImludmFsaWRcIl0nKS5sZW5ndGgpIHtcbiAgICAgICQoJy5jaGVja291dC1idXR0b24nKS5yZW1vdmVBdHRyKCdocmVmJykuYWRkQ2xhc3MoJ2Rpc2FibGVkJyk7XG4gICAgICAkKCcud2NwcGVjLWNoZWNrb3V0LWJ1dHRvbnMnKS5oaWRlKCk7XG4gICAgfVxuICB9KTtcblxuICAvLyBVc2VkIGFuZCBkZWZlY3RpdmUgZ29vZHMgY2hlY2tib3ggYWdyZWVtZW50IHJlbGF0ZWQgZnVuY3Rpb25hbGl0eS5cbiAgJChkb2N1bWVudCkucmVhZHkoKCkgPT4ge1xuICAgIGNvbnN0ICR1c2VkR29vZHNDb25zZW50Q2hlY2tib3ggPSAkKCcjdXNlZC1nb29kcy1jb25zZW50Jyk7XG4gICAgY29uc3QgJHVzZWRHb29kc0NvbnNlbnRDb250YWluZXIgPSAkKCcucHJvZHVjdC1kZWZlY3RzX19jaGVja2JveC1jb250YWluZXInKTtcbiAgICBpZiAoJHVzZWRHb29kc0NvbnNlbnRDaGVja2JveC5sZW5ndGgpIHtcbiAgICAgICQoZG9jdW1lbnQpXG4gICAgICAgIC5vbignc2hvd192YXJpYXRpb24nLCAnLnNpbmdsZV92YXJpYXRpb25fd3JhcCcsIChldmVudCwgdmFyaWF0aW9uKSA9PiB7XG4gICAgICAgICAgY29uc3QgdXNlZEdvb2RzQ29uc2VudEF0dHIgPSB2YXJpYXRpb24udXNlZF9nb29kc19jb25zZW50X2F0dHJpYnV0ZTtcbiAgICAgICAgICAkKCcucHJvZHVjdC1kZWZlY3RzX19hdHRyaWJ1dGUnKS50ZXh0KHVzZWRHb29kc0NvbnNlbnRBdHRyKTtcbiAgICAgICAgICBpZiAodXNlZEdvb2RzQ29uc2VudEF0dHIpIHtcbiAgICAgICAgICAgICR1c2VkR29vZHNDb25zZW50Q29udGFpbmVyLnNob3coKTtcbiAgICAgICAgICAgICR1c2VkR29vZHNDb25zZW50Q2hlY2tib3gucHJvcCgncmVxdWlyZWQnLCAncmVxdWlyZWQnKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJHVzZWRHb29kc0NvbnNlbnRDb250YWluZXIuaGlkZSgpO1xuICAgICAgICAgICAgJHVzZWRHb29kc0NvbnNlbnRDaGVja2JveC5yZW1vdmVBdHRyKCdyZXF1aXJlZCcpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfVxuICB9KTtcbn0oalF1ZXJ5KSk7XG4iXSwiZmlsZSI6Im1haW4uanMifQ==