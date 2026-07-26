(function(w) {
  'use strict';

  w.PhantomCore = {
    adapters: null,
    renderer: null,
    services: null,
    initialized: false,

    init: function() {
      if (this.initialized) return;
      this.adapters = w.PhantomAdapters || {};
      this.renderer = w.PhantomRenderer || {};
      this.services = w.PhantomServices || {};
      this.initialized = true;

      document.addEventListener('DOMContentLoaded', this.onReady.bind(this));
    },

    onReady: function() {
      var services = this.services;
      var adapters = this.adapters;
      var renderer = this.renderer;

      services.Api.getPageData().then(function(data) {
        if (!data) return;
        window.PhantomInjector.injectSettings(data.settings);
        window.PhantomInjector.injectMenus(data.menus);
        window.PhantomInjector.injectProducts(data.products, data.settings);
      }).catch(function(err) {
        console.error('[PhantomCore] Init error:', err);
      });
    },
  };

  w.PhantomCore.init();

  // Initialize PhantomInjector with data from PhantomData
  (function () {
    if (!window.PhantomInjector) return;

    if (window.PhantomData) {
      if (window.PhantomData.settings) {
        window.PhantomInjector.injectSettings(window.PhantomData.settings);
      }
      if (window.PhantomData.menus) {
        window.PhantomInjector.injectMenus(window.PhantomData.menus);
      }
      if (window.PhantomData.products) {
        window.PhantomInjector.injectProducts(window.PhantomData.products);
      }
    }
  })();
})(window);
