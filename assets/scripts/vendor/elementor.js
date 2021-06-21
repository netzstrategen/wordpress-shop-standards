/* global jQuery */

(function elementorAnchor($) {
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
