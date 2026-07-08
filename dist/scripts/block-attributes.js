'use strict';

/**
 * Progressive enhancement: collapses product attributes in the Cart and
 * Checkout blocks.
 *
 * Both blocks render line-item metadata client-side from the Store API, so
 * there is no server template to hook. Inside each item's
 * `.wc-block-components-product-metadata` container the block renders two
 * `.wc-block-components-product-details` lists: the first holds the item data
 * (SKU, delivery time and — tagged by `WooCommerce::woocommerce_get_item_data()`
 * with `shop-standards-attribute--additional` — the product attributes); the
 * second holds the selected variation attributes.
 *
 * To match the classic cart, everything except the leading item data is
 * grouped under a toggle: the tagged attribute rows and the whole variation
 * list. Collapsed is the CSS default (no flash); the script only reveals on
 * expand and re-applies after the block re-renders.
 */
(function cartBlockAttributes(wp, wc) {
  const ADDITIONAL_CLASS = 'shop-standards-attribute--additional';
  const TOGGLE_CLASS = 'shop-standards-attributes-toggle';
  const EXPANDED_CLASS = 'is-attributes-expanded';
  const META_SELECTOR = '.wc-block-components-product-metadata';
  const DETAILS_SELECTOR = '.wc-block-components-product-details';

  const getSetting = wc && wc.wcSettings && wc.wcSettings.getSetting;
  const settings = getSetting ? getSetting('shop-standards-blocks_data', {}) : {};
  const LABEL = settings.productAttributesLabel || 'Produkteigenschaften';

  // Expanded state lives outside the DOM: WooCommerce re-renders on every
  // cart/checkout change and would otherwise reset our classes. Keyed per
  // metadata container; a replaced node resets to collapsed, matching the
  // classic default.
  const expandedMetas = new WeakSet();

  /**
   * Syncs a container's reveal class and the toggle's aria-expanded to state.
   *
   * @param {HTMLElement} meta
   *   The `.wc-block-components-product-metadata` container.
   */
  function applyState(meta) {
    const isExpanded = expandedMetas.has(meta);
    const button = meta.querySelector('.' + TOGGLE_CLASS + ' button');
    if (button) {
      button.setAttribute('aria-expanded', String(isExpanded));
    }
    meta.classList.toggle(EXPANDED_CLASS, isExpanded);
  }

  /**
   * Flips the stored expanded state of a container and re-applies it.
   *
   * @param {HTMLElement} meta
   *   The metadata container.
   */
  function toggle(meta) {
    if (expandedMetas.has(meta)) {
      expandedMetas.delete(meta);
    }
    else {
      expandedMetas.add(meta);
    }
    applyState(meta);
  }

  /**
   * Adds the toggle to a single metadata container, if it has collapsible
   * content (tagged product attributes and/or a variation list).
   *
   * @param {HTMLElement} meta
   *   The metadata container.
   */
  function enhanceMeta(meta) {
    const lists = meta.querySelectorAll(':scope > ' + DETAILS_SELECTOR);
    if (!lists.length) {
      return;
    }
    const primaryList = lists[0];
    const tagged = primaryList.querySelectorAll(':scope > li.' + ADDITIONAL_CLASS);
    // A trailing list (sibling of the item-data list) holds variation attributes.
    const hasVariationList = lists.length > 1 && lists[lists.length - 1].children.length > 0;
    if (!tagged.length && !hasVariationList) {
      return;
    }

    // Insert the toggle once, after the primary rows (before the attributes).
    if (!meta.querySelector('.' + TOGGLE_CLASS)) {
      const item = document.createElement('li');
      item.className = TOGGLE_CLASS;

      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = LABEL;
      button.addEventListener('click', () => toggle(meta));

      item.appendChild(button);
      if (tagged.length) {
        primaryList.insertBefore(item, tagged[0]);
      }
      else {
        primaryList.appendChild(item);
      }
    }

    applyState(meta);
  }

  /**
   * Enhances every product-metadata container currently in the DOM.
   */
  function enhanceAll() {
    const metas = document.querySelectorAll(META_SELECTOR);
    try {
      metas.forEach(enhanceMeta);
    }
    catch (error) {
      // Never break the cart/checkout: reveal everything as a safe fallback.
      metas.forEach((meta) => meta.classList.add(EXPANDED_CLASS));
    }
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
      enhanceAll();
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
