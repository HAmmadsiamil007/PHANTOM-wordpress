(function($) {
    'use strict';

    wp.customize.controlConstructor['ast-color'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;

            control.initPicker();

            control.container.on('click', '.ast-color-swatch', function() {
                var color = $(this).data('color');
                control.setting.set(color);
                control.container.find('.ast-color-swatch').removeClass('active');
                $(this).addClass('active');
            });

            control.setting.bind(function(value) {
                control.params.value = value;
                control.renderContent();
                control.initPicker();
            });
        },

        initPicker: function() {
            var control = this;
            var container = control.container;
            var picker = container.find('.ast-color-picker');
            var hidden = container.find('.ast-color-value');

            if (picker.length) {
                picker.wpColorPicker({
                    change: function(event, ui) {
                        hidden.val(ui.color.toString()).trigger('change');
                        control.setting.set(ui.color.toString());
                    },
                    clear: function() {
                        hidden.val('').trigger('change');
                        control.setting.set('');
                    }
                });
            }
        }
    });
})(jQuery);
