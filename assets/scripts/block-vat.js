'use strict';

/**
 * Conditional VAT field for the block checkout.
 *
 * The WooCommerce EU VAT Number plugin renders its VAT field as a checkout
 * step (wrapper `.eu-vat-extra-css`) for every EU country and ignores the
 * salutation. Business rule: show it only for EU countries other than
 * Germany AND when the salutation is "Company". We keep the plugin — so VIES
 * validation and reverse-charge stay intact — and only toggle its visibility.
 *
 * The step is hidden by default via CSS (see assets/styles/blocks.scss) and
 * revealed here only when the rule matches; when it does not, any entered VAT
 * is cleared so reverse-charge is not applied to a non-qualifying customer.
 * The EU restriction is already handled by the plugin (it renders the step
 * only for EU countries), so we only add "not DE" + "Company".
 */
(function checkoutVatConditional(wp) {
  const VAT_STEP = '.eu-vat-extra-css';
  const VAT_INPUT = '#billing_vat_number';
  const VAT_HEADING = '.eu-vat-extra-css .wc-block-components-checkout-step__title, .eu-vat-extra-css legend';
  const VISIBLE_CLASS = 'shop-standards-vat-visible';
  const SALUTATION_FIELD = 'shop-standards/salutation';

  // Rewords the step heading from "VAT Details" to "Umsatzsteuer-ID".
  function fixHeadingText() {
    document.querySelectorAll(VAT_HEADING).forEach((el) => {
      if (el.textContent.trim() === 'VAT Details') {
        el.textContent = 'Umsatzsteuer-ID';
      }
    });
  }

  /**
   * Whether the VAT step should be shown for the current checkout state.
   *
   * @return {boolean}
   */
  function shouldShow() {
    const store = wp.data.select('wc/store/cart');
    if (!store) {
      return false;
    }
    const billing = store.getCartData().billingAddress || {};
    const country = billing.country || '';
    const salutation = billing[SALUTATION_FIELD] || '';
    return country !== '' && country !== 'DE' && salutation === 'Company';
  }

  /**
   * Clears the VAT input via the native setter so the plugin's React state and
   * the Store API value update too (removing any reverse-charge).
   */
  function clearVat() {
    const input = document.querySelector(VAT_INPUT);
    if (!input || !input.value) {
      return;
    }
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(input, '');
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /**
   * Applies the visibility rule to the VAT step, if present.
   */
  function apply() {
    const step = document.querySelector(VAT_STEP);
    if (!step) {
      return;
    }
    fixHeadingText();
    const show = shouldShow();
    step.classList.toggle(VISIBLE_CLASS, show);
    if (!show) {
      clearVat();
    }
  }

  // Coalesce the burst of updates an address/field change produces.
  let scheduled = false;
  function schedule() {
    if (scheduled) {
      return;
    }
    scheduled = true;
    window.requestAnimationFrame(() => {
      scheduled = false;
      try {
        apply();
      }
      catch (error) {
        // Never break checkout: fall back to the CSS default (step hidden).
      }
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
}(window.wp));
