'use strict';

/**
 * Adjusts Cart/Checkout block labels — via the official checkout filter
 * registry where a filter exists, otherwise via direct DOM text updates.
 *
 * Removes the leading "Veranschlagte" from the total label ("Veranschlagte
 * Gesamtsumme" -> "Gesamtsumme"), which German Market prints on the block
 * total. This is a client-side block string, so it cannot be changed with the
 * PHP `gettext` filter; the `totalLabel` filter is the supported way to
 * override it in the Cart and Checkout blocks. The regex only matches that
 * specific prefix, so it is a no-op on stores without German Market.
 *
 * Per Asana ID-835, the sale badge's leading "Sparen Sie" (imperative, "please
 * save") should read "Sie sparen" (declarative, "you save"). No checkout
 * filter exposes it: `saleBadgePriceFormat` only formats what replaces the
 * price placeholder, and "Sparen Sie " is a separate, fixed text node before
 * it. Rewriting that node directly is the only way to change it.
 */
(function cartBlockLabels(wp, wc) {
  if (wc && wc.blocksCheckout && typeof wc.blocksCheckout.registerCheckoutFilters === 'function') {
    wc.blocksCheckout.registerCheckoutFilters('shop-standards-block-labels', {
      /**
       * @param {string} value
       *   The default (localized) total label.
       * @return {string}
       *   The label without the leading "Veranschlagte".
       */
      totalLabel: (value) => (typeof value === 'string' ? value.replace(/^Veranschlagte\s+/i, '') : value),
    });
  }

  const BADGE_SELECTOR = '.wc-block-components-sale-badge';

  /**
   * Rewrites a single badge's leading text node, if not already fixed.
   *
   * @param {HTMLElement} badge
   *   The sale badge element.
   */
  function fixBadgeText(badge) {
    const textNode = badge.firstChild;
    if (textNode && textNode.nodeType === Node.TEXT_NODE) {
      textNode.textContent = textNode.textContent.replace(/^(\s*)Sparen Sie\b/, '$1Sie sparen');
    }
  }

  function fixAllBadges() {
    document.querySelectorAll(BADGE_SELECTOR).forEach(fixBadgeText);
  }

  // Coalesce the burst of updates a single cart change produces.
  let scheduled = false;
  function schedule() {
    if (scheduled) {
      return;
    }
    scheduled = true;
    window.requestAnimationFrame(() => {
      scheduled = false;
      fixAllBadges();
    });
  }

  schedule();

  // Re-apply after Store API cart updates (primary signal).
  if (wp && wp.data && typeof wp.data.subscribe === 'function') {
    wp.data.subscribe(schedule, 'wc/store/cart');
  }

  // Safety net: catch the initial render and any DOM change we didn't observe.
  if (typeof MutationObserver === 'function') {
    new MutationObserver(schedule).observe(document.body, {
      childList: true,
      subtree: true,
    });
  }
}(window.wp, window.wc));
