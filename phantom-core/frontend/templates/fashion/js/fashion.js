(function () {
  'use strict';

  // Mobile menu toggle
  var toggle = document.querySelector('[data-phantom-menu-toggle]');
  var nav = document.querySelector('.primary-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('open');
      toggle.classList.toggle('open');
    });
  }

  // Quantity selector
  document.querySelectorAll('[data-phantom-qty-minus]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.parentElement.querySelector('[data-phantom-qty-input]');
      if (input) {
        var val = parseInt(input.value, 10) || 1;
        if (val > 1) input.value = val - 1;
      }
    });
  });
  document.querySelectorAll('[data-phantom-qty-plus]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.parentElement.querySelector('[data-phantom-qty-input]');
      if (input) {
        var val = parseInt(input.value, 10) || 1;
        input.value = val + 1;
      }
    });
  });

  // Product gallery thumbnails
  document.querySelectorAll('.gallery-thumb').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var container = thumb.closest('.gallery-thumbnails');
      if (container) {
        container.querySelectorAll('.gallery-thumb').forEach(function (t) { t.classList.remove('active'); });
      }
      thumb.classList.add('active');
      var mainImg = thumb.closest('.product-gallery').querySelector('[data-phantom-product-image]');
      var thumbImg = thumb.querySelector('img');
      if (mainImg && thumbImg) {
        mainImg.src = thumbImg.src;
        mainImg.alt = thumbImg.alt;
      }
    });
  });

  // Accordion
  document.querySelectorAll('.accordion-item summary').forEach(function (summary) {
    summary.addEventListener('click', function (e) {
      e.preventDefault();
      var details = summary.parentElement;
      var open = details.hasAttribute('open');
      // Close all siblings
      var accordion = details.closest('.product-accordion');
      if (accordion) {
        accordion.querySelectorAll('.accordion-item[open]').forEach(function (item) {
          if (item !== details) item.removeAttribute('open');
        });
      }
      if (open) {
        details.removeAttribute('open');
      } else {
        details.setAttribute('open', '');
      }
    });
  });

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var href = anchor.getAttribute('href');
      if (href && href.length > 1) {
        var target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth' });
        }
      }
    });
  });

  // Cart count update listener
  document.addEventListener('phantom:cart:updated', function (e) {
    var count = e.detail && e.detail.count !== undefined ? e.detail.count : 0;
    document.querySelectorAll('[data-phantom-cart-count]').forEach(function (el) {
      el.textContent = count;
    });
  });

})();