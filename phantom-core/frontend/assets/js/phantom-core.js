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

  // Backward-compatible PhantomBridge shim
  (function() {
    if (w.PhantomBridge) return;
    w.PhantomBridge = {
      init: function() { return this; },
      getSetting: function(key) {
        return w.PhantomData ? w.PhantomData[key] : undefined;
      },
      setSetting: function(key, value) {
        return w.PhantomServices && w.PhantomServices.Api
          ? w.PhantomServices.Api.setSetting(key, value)
          : Promise.reject(new Error('API service unavailable'));
      },
      saveChanges: function(changes) {
        return w.PhantomServices && w.PhantomServices.Api
          ? w.PhantomServices.Api.saveChanges(changes)
          : Promise.reject(new Error('API service unavailable'));
      },
      onSettingChange: function(key, fn) {
        w.PhantomEvents && w.PhantomEvents.onSettingChange(key, fn);
        return this;
      },
      offSettingChange: function(key, fn) {
        w.PhantomEvents && w.PhantomEvents.offSettingChange(key, fn);
        return this;
      },
      highlightElement: function(selector) {
        var el = document.querySelector(selector);
        if (el) {
          el.classList.add('phantom-highlight');
          setTimeout(function() { el.classList.remove('phantom-highlight'); }, 2000);
        }
      },
      openEditor: function(key) {
        var url = (w.wpAdminUrl || '/wp-admin/') + 'customize.php?autofocus[control]=phantom_' + encodeURIComponent(key);
        window.open(url, '_blank');
      },
      getCssVars: function() {
        return {};
      }
    };
  })();
})(window);
