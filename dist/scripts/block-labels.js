'use strict';

/**
 * Adjusts Cart/Checkout block labels: via the checkout filter registry where
 * a filter exists, otherwise via direct DOM text updates.
 */
(function cartBlockLabels(wp, wc) {
  if (wc && wc.blocksCheckout && typeof wc.blocksCheckout.registerCheckoutFilters === 'function') {
    wc.blocksCheckout.registerCheckoutFilters('shop-standards-block-labels', {
      // Shortens WooCommerce core's "Estimated total" label (de: "Veranschlagte
      // Gesamtsumme") to just "Gesamtsumme".
      totalLabel: (value) => (typeof value === 'string' ? value.replace(/^Veranschlagte\s+/i, '') : value),
    });
  }

  const BADGE_SELECTOR = '.wc-block-components-sale-badge';

  // Rewords the sale badge's leading text node from "Sparen Sie" to "Sie
  // sparen"; no checkout filter covers this fixed text node.
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

  if (wp && wp.data && typeof wp.data.subscribe === 'function') {
    wp.data.subscribe(schedule, 'wc/store/cart');
  }

  if (typeof MutationObserver === 'function') {
    new MutationObserver(schedule).observe(document.body, {
      childList: true,
      subtree: true,
    });
  }
}(window.wp, window.wc));
