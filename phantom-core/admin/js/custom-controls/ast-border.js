(function ($) {
  'use strict';

  wp.customize.controlConstructor['ast-border'] = wp.customize.Control.extend({
    ready: function () {
      var control = this;

      control.initBorder();

      control.setting.bind(function(value) {
        control.params.value = value;
        control.renderContent();
        control.initBorder();
      });
    },

    initBorder: function () {
      var control = this;
      var fields = control.container.find('.ast-border-fields');

      function collectValue() {
        return {
          top: parseFloat(fields.find('.ast-border-top').val()) || 0,
          right: parseFloat(fields.find('.ast-border-right').val()) || 0,
          bottom: parseFloat(fields.find('.ast-border-bottom').val()) || 0,
          left: parseFloat(fields.find('.ast-border-left').val()) || 0,
          color: fields.find('.ast-border-color').val(),
          radius: parseFloat(fields.find('.ast-border-radius').val()) || 0,
          linked: fields.find('.ast-border-linked').val() === '1'
        };
      }

      if (fields.find('.ast-border-color').length) {
        fields.find('.ast-border-color').wpColorPicker({
          change: function () { control.setting.set(collectValue()); }
        });
      }
    }
  });

})(jQuery);
