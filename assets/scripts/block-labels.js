'use strict';

/**
 * Adjusts Cart/Checkout block labels via the official checkout filter registry.
 *
 * Removes the leading "Veranschlagte" from the total label ("Veranschlagte
 * Gesamtsumme" -> "Gesamtsumme"), which German Market prints on the block
 * total. This is a client-side block string, so it cannot be changed with the
 * PHP `gettext` filter; the `totalLabel` filter is the supported way to
 * override it in the Cart and Checkout blocks. The regex only matches that
 * specific prefix, so it is a no-op on stores without German Market.
 */
(function cartBlockLabels(wc) {
  if (!wc || !wc.blocksCheckout || typeof wc.blocksCheckout.registerCheckoutFilters !== 'function') {
    return;
  }

  wc.blocksCheckout.registerCheckoutFilters('shop-standards-block-labels', {
    /**
     * @param {string} value
     *   The default (localized) total label.
     * @return {string}
     *   The label without the leading "Veranschlagte".
     */
    totalLabel: (value) => (typeof value === 'string' ? value.replace(/^Veranschlagte\s+/i, '') : value),
  });
}(window.wc));
