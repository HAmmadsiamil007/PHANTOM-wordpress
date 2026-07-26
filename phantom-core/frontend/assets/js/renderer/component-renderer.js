(function(w) {
  'use strict';

  w.PhantomRenderer = w.PhantomRenderer || {};

  w.PhantomRenderer.ComponentRenderer = {
    renderTemplate: function(tpl, data) {
      return tpl.replace(/\{\{(\w+)\}\}/g, function(m, k) {
        return data[k] !== undefined ? data[k] : m;
      });
    },

    escapeHtml: function(str) {
      if (!str) return '';
      var d = document.createElement('div');
      d.textContent = str;
      return d.innerHTML;
    },

    sanitizeUrl: function(url) {
      if (!url) return '#';
      url = url.trim();
      if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) return url;
      return '#';
    },

    getCurrencySymbol: function() {
      return w.PhantomData && w.PhantomData.currency_symbol
        ? w.PhantomData.currency_symbol : '$';
    },

    createElement: function(html) {
      var d = document.createElement('div');
      d.innerHTML = html;
      return d.firstElementChild || d.firstChild;
    }
  };
})(window);
