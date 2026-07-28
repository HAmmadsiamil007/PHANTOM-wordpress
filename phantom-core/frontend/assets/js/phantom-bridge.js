/**
 * Phantom Bridge - Utility Helpers
 */
(function() {
  'use strict';
  var PhantomBridge = {
    getSetting: function(key, defaultValue) {
      if (window.phantomData && window.phantomData.settings) {
        return window.phantomData.settings[key] || defaultValue;
      }
      return defaultValue;
    },
    getCookie: function(name) {
      var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
      return match ? decodeURIComponent(match[2]) : null;
    },
    setCookie: function(name, value, days) {
      var expires = '';
      if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
      }
      document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
    },
    debounce: function(func, wait) {
      var timeout;
      return function() {
        var context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() { func.apply(context, args); }, wait);
      };
    },
    throttle: function(func, limit) {
      var inThrottle;
      return function() {
        var args = arguments, context = this;
        if (!inThrottle) {
          func.apply(context, args);
          inThrottle = true;
          setTimeout(function() { inThrottle = false; }, limit);
        }
      };
    },
    isMobile: function() { return window.innerWidth <= 768; },
    scrollTo: function(selector, offset) {
      var el = document.querySelector(selector);
      if (el) {
        var top = el.getBoundingClientRect().top + window.pageYOffset - (offset || 0);
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    }
  };
  window.PhantomBridge = PhantomBridge;
})();
