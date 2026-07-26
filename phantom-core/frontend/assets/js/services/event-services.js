(function(w) {
  'use strict';

  w.PhantomEvents = w.PhantomEvents || {
    handlers: {},
    settingHandlers: {},

    on: function(event, fn) {
      if (!this.handlers[event]) this.handlers[event] = [];
      this.handlers[event].push(fn);
      return this;
    },

    off: function(event, fn) {
      if (this.handlers[event]) {
        this.handlers[event] = this.handlers[event].filter(function(f) { return f !== fn; });
      }
      return this;
    },

    emit: function(event, data) {
      var list = this.handlers[event];
      if (list) {
        for (var i = 0; i < list.length; i++) {
          try { list[i](data); } catch (e) { console.warn('[PhantomEvents] handler error', e); }
        }
      }
    },

    onSettingChange: function(key, fn) {
      if (!this.settingHandlers[key]) this.settingHandlers[key] = [];
      this.settingHandlers[key].push(fn);
      return this;
    },

    offSettingChange: function(key, fn) {
      if (this.settingHandlers[key]) {
        this.settingHandlers[key] = this.settingHandlers[key].filter(function(f) { return f !== fn; });
      }
      return this;
    },

    emitSettingChange: function(key, value) {
      var list = this.settingHandlers[key] || [];
      for (var i = 0; i < list.length; i++) {
        try { list[i](value, key); } catch (e) { console.warn('[PhantomEvents] setting handler error', e); }
      }
    }
  };
})(window);
