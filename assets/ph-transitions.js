(function() {
  'use strict';

  if (!window.phMotionSettings || !window.phMotionSettings.viewTransitionsEnabled) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (!document.startViewTransition) return;

  var skipSelectors = [
    'a[href*="/cart"]', 'a[href*="/checkout"]',
    'a[href*="/account"]',
    'a[href*="tel:"]', 'a[href*="mailto:"]',
    'a[download]', 'a[target="_blank"]',
    'a[href^="#"]', 'button', 'input', 'select', 'textarea',
    '[data-drawer-trigger]', '[data-modal-trigger]',
    '.drawer__trigger', '.modal__trigger',
    '.flickity-button', '.flickity-page-dots a',
    '.predictive-search__result a',
    '.rte a', '.shopify-challenge__container a',
    '.social-sharing a', '.ajaxcart a',
    '.header__icon--cart', '.site-nav__link--icon',
    'a.quick-product__btn', 'a.quick-shop__trigger',
    '[data-action]', '[data-no-transition]'
  ].join(',');

  document.addEventListener('click', function(e) {
    var link = e.target.closest('a');
    if (!link) return;
    if (link.matches(skipSelectors)) return;
    if (link.closest('[data-disable-animations="true"]')) return;

    var href = link.getAttribute('href');
    if (!href || href === '' || href === '#') return;

    var isExternal = href.indexOf('http') === 0 && href.indexOf(window.location.origin) !== 0;
    var isProtocol = href.indexOf('tel:') === 0 || href.indexOf('mailto:') === 0;
    if (isExternal || isProtocol) return;

    var samePage = (function() {
      try {
        var url = new URL(href, window.location.origin);
        return url.pathname === window.location.pathname && url.search === window.location.search;
      } catch(e) {
        return false;
      }
    })();
    if (samePage) return;

    e.preventDefault();
    var targetUrl = link.href;

    document.startViewTransition(function() {
      window.location.href = targetUrl;
    });
  });

  window.addEventListener('popstate', function() {
    if (document.startViewTransition) {
      document.startViewTransition(function() {
        window.location.reload();
      });
    }
  });
})();
