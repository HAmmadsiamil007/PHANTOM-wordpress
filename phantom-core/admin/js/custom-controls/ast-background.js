(function ($) {
  'use strict';

  wp.customize.controlConstructor['ast-background'] = wp.customize.Control.extend({
    ready: function () {
      var control = this;

      control.initBackground();

      control.setting.bind(function(value) {
        control.params.value = value;
        control.renderContent();
        control.initBackground();
      });
    },

    initBackground: function () {
      var control = this;
      var fields = control.container.find('.ast-background-fields');

      function collectValue() {
        return {
          color: fields.find('.ast-bg-color').val(),
          image: fields.find('.ast-bg-image').val(),
          position: fields.find('.ast-bg-position').val(),
          repeat: fields.find('.ast-bg-repeat').val(),
          size: fields.find('.ast-bg-size').val(),
          attachment: fields.find('.ast-bg-attachment').val(),
          overlay_color: fields.find('.ast-bg-overlay-color').val(),
          overlay_opacity: parseFloat(fields.find('.ast-bg-overlay-opacity').val()) || 0.5
        };
      }

      if (fields.find('.ast-bg-color').length) {
        fields.find('.ast-bg-color').wpColorPicker({
          change: function () { control.setting.set(collectValue()); }
        });
      }

      fields.on('change', 'input, select', function () {
        control.setting.set(collectValue());
      });
    }
  });

})(jQuery);
