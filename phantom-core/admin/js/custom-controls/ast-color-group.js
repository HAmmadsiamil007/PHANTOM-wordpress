(function ($) {
  'use strict';

  wp.customize.controlConstructor['ast-color-group'] = wp.customize.Control.extend({
    ready: function () {
      var control = this;

      control.initGroup();

      control.setting.bind(function(value) {
        control.params.value = value;
        control.renderContent();
        control.initGroup();
      });
    },

    initGroup: function () {
      var control = this;
      var container = control.container.find('.ast-color-group-items');
      if (!container.length) return;
      var groupId = container.data('group-id');

      if (control.params.input_attrs && control.params.input_attrs.children) {
        var children = control.params.input_attrs.children;
        children.forEach(function (child) {
          var row = $('<div class="ast-color-group-item"></div>');
          var label = $('<label class="ast-color-group-label">' + child.label + '</label>');
          var input = $('<input type="text" class="ast-color-picker" value="' + (child.value || '') + '" />');
          var hidden = $('<input type="hidden" data-customize-setting-link="' + child.setting + '" value="' + (child.value || '') + '" />');
          row.append(label).append(input).append(hidden);
          container.append(row);

          input.wpColorPicker({
            change: function () {
              hidden.val(input.val()).trigger('change');
            },
            clear: function () {
              hidden.val('').trigger('change');
            }
          });
        });
      }
    }
  });

})(jQuery);
