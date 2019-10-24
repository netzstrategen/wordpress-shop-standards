(function pageLoad($) {
  /**
   * Checks GTIN for uniqueness and issues an error/success message.
   */
  $(document).on('keyup', "input[name*='_shop-standards_gtin']", _.debounce((event) => {
    const $gtinInput = $(event.currentTarget);
    const $gtinField = $gtinInput.parents('.form-field');
    $gtinField.find('.notice').remove();
    if ($gtinField.find('.spinner').length === 0) {
      $gtinField.append('<span class="spinner"></span>');
    }
    $gtinField.find('.spinner').addClass('is-active');

    jQuery.post(
      ajaxurl,
      {
        action: 'is_existing_gtin',
        product_id: shop_standards_admin.product_id,
        gtin: $gtinInput.val(),
      },
      function (response) {
        $gtinField.find('.spinner').removeClass('is-active');
        $gtinField.find('.notice').remove();

        if (response.is_unique) {
          const message = shop_standards_admin.gtin_success_message;
          $gtinField.append(`
            <div class="notice notice-success">
              ${message}
            </div>
          `);
        }
        else {
          const message = shop_standards_admin.gtin_error_message.replace('{{url}}', response.duplicate_edit_link);
          $gtinField.append(`
            <div class="notice notice-error">
              ${message}
            </div>
          `);
        }
      },
      'json',
    );
  }, 500));
}(jQuery));
