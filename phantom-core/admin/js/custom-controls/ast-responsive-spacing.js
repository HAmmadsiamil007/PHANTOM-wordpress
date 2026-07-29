(function($) {
    'use strict';

    wp.customize.controlConstructor['ast-responsive-spacing'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;

            control.initSpacing();

            control.setting.bind(function(value) {
                control.params.value = value;
                control.renderContent();
                control.initSpacing();
            });
        },

        initSpacing: function() {
            var control = this;
            var container = control.container;
            var hidden = container.find('.ast-responsive-spacing-value');
            var linked = false;

            container.find('.ast-device-tab').on('click', function() {
                var device = $(this).data('device');
                container.find('.ast-device-tab').removeClass('active');
                $(this).addClass('active');
                container.find('.ast-spacing-device').hide();
                container.find('.ast-spacing-device[data-device="' + device + '"]').show();
            });

            container.find('.ast-spacing-linked').on('click', function() {
                linked = !linked;
                $(this).toggleClass('linked');
            });

            function update() {
                var val = {};
                container.find('.ast-spacing-input').each(function() {
                    var dev = $(this).data('device');
                    var dir = $(this).data('direction');
                    if (!val[dev]) val[dev] = {};
                    val[dev][dir] = parseFloat($(this).val()) || 0;
                });
                hidden.val(JSON.stringify(val)).trigger('change');
                control.setting.set(val);
            }

            container.find('.ast-spacing-input').on('input', function() {
                if (linked) {
                    var v = $(this).val();
                    var dev = $(this).data('device');
                    container.find('.ast-spacing-input[data-device="' + dev + '"]').val(v);
                }
                update();
            });
        }
    });
})(jQuery);
