(function ($) {
  'use strict';

  function evalCondition(value, operator, target) {
    switch (operator) {
      case '===': return value === target;
      case '!==': return value !== target;
      case 'in': return Array.isArray(target) && target.indexOf(value) !== -1;
      default: return value == target;
    }
  }

  function shouldShow(deps, getter) {
    for (var i = 0; i < deps.length; i++) {
      var dep = deps[i];
      var depKey = dep.key || (typeof dep === 'object' ? Object.keys(dep)[0] : null);
      var depVal = dep.value !== undefined ? dep.value : (typeof dep === 'object' ? dep[Object.keys(dep)[0]] : null);
      var operator = dep.operator || '===';
      if (!depKey) continue;
      var current = getter(depKey);
      if (depVal === null || dep.value === undefined) {
        if (!current) return false;
        continue;
      }
      if (!evalCondition(current, operator, depVal)) return false;
    }
    return true;
  }

  function initConditionals() {
    wp.customize.control.each(function (control) {
      var params = control.params || {};
      var inputAttrs = params.input_attrs || {};
      var deps = inputAttrs['data-dependencies'];
      if (!deps || !deps.length) return;

      var settingId = params.settings && params.settings.default ? params.settings.default : control.id;
      var container = control.container;

      function getSettingValue(key) {
        var fullKey = key.indexOf('phantom_') === 0 ? key : 'phantom_' + key;
        var s = wp.customize(fullKey);
        return s ? s() : null;
      }

      function updateVisibility() {
        var visible = shouldShow(deps, getSettingValue);
        container.toggle(visible);
      }

      for (var i = 0; i < deps.length; i++) {
        var dep = deps[i];
        var depKey = dep.key || Object.keys(dep)[0];
        var fullKey = depKey.indexOf('phantom_') === 0 ? depKey : 'phantom_' + depKey;
        var setting = wp.customize(fullKey);
        if (setting) {
          setting.bind(updateVisibility);
        }
      }

      updateVisibility();
    });
  }

  wp.customize.bind('ready', initConditionals);

  function initDividers() {
    if (typeof PhantomDividerControls === 'undefined') return;
    wp.customize.control.each(function (control) {
      var divider = PhantomDividerControls[control.id];
      if (!divider) return;
      var cssClass = divider.ast_class || 'ast-top-divider';
      var container = control.container;
      if (container.length) {
        container.prepend('<div class="' + cssClass + '"></div>');
      }
    });
  }

  wp.customize.bind('ready', initDividers);

})(jQuery);
