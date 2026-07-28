(function(w) {
  'use strict';
  if (w.PhantomBridge) return;
  w.PhantomBridge = {
    getSetting: function(key) {
      return w.PhantomData ? w.PhantomData[key] : undefined;
    },
    setSetting: function(key, value) {
      return w.PhantomApi ? w.PhantomApi.setSetting(key, value)
        : Promise.reject(new Error('API unavailable'));
    },
    onSettingChange: function(key, fn) {
      w.PhantomEvents && w.PhantomEvents.onSettingChange(key, fn);
      return this;
    },
    offSettingChange: function(key, fn) {
      w.PhantomEvents && w.PhantomEvents.offSettingChange(key, fn);
      return this;
    }
  };
})(window);
