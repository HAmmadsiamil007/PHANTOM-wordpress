(function($) {
    'use strict';

    wp.customize.controlConstructor['ast-responsive-slider'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;

            control.initSlider();

            control.setting.bind(function(value) {
                control.params.value = value;
                control.renderContent();
                control.initSlider();
            });
        },

        initSlider: function() {
            var control = this;
            var container = control.container;
            var hidden = container.find('.ast-responsive-slider-value');

            container.find('.ast-device-tab').on('click', function() {
                var device = $(this).data('device');
                container.find('.ast-device-tab').removeClass('active');
                $(this).addClass('active');
                container.find('.ast-slider-device').hide();
                container.find('.ast-slider-device[data-device="' + device + '"]').show();
            });

            function update() {
                var val = {};
                container.find('.ast-responsive-slider').each(function() {
                    val[$(this).data('device')] = parseFloat($(this).val());
                });
                hidden.val(JSON.stringify(val)).trigger('change');
                control.setting.set(val);
            }

            container.find('.ast-responsive-slider').on('input', function() {
                var val = $(this).val();
                var device = $(this).data('device');
                container.find('.ast-responsive-slider-input[data-device="' + device + '"]').val(val);
                update();
            });

            container.find('.ast-responsive-slider-input').on('input', function() {
                var val = $(this).val();
                var device = $(this).data('device');
                container.find('.ast-responsive-slider[data-device="' + device + '"]').val(val);
                update();
            });
        }
    });
})(jQuery);
