(function($) {
    'use strict';

    wp.customize.controlConstructor['ast-typography'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;

            control.initTypography();

            control.setting.bind(function(value) {
                control.params.value = value;
                control.renderContent();
                control.initTypography();
            });
        },

        initTypography: function() {
            var control = this;
            var container = control.container;
            var hidden = container.find('.ast-typography-value');
            var googleFonts = typeof PhantomFonts !== 'undefined' ? PhantomFonts.google : {};

            container.find('.ast-device-tab').on('click', function() {
                var device = $(this).data('device');
                container.find('.ast-device-tab').removeClass('active');
                $(this).addClass('active');
                container.find('.ast-typography-size-device').hide();
                container.find('.ast-typography-size-device[data-device="' + device + '"]').show();
            });

            container.find('.ast-typography-family').on('change', function() {
                var family = $(this).val();
                var weightSelect = container.find('.ast-typography-weight');
                weightSelect.find('option').show();

                if (googleFonts[family]) {
                    var available = googleFonts[family].weights || [];
                    weightSelect.find('option').each(function() {
                        if ($.inArray($(this).val(), available) === -1) {
                            $(this).hide();
                        }
                    });
                    if ($.inArray(weightSelect.val(), available) === -1) {
                        weightSelect.val(available[0] || '400');
                    }
                }
                update();
            });

            function update() {
                var val = {
                    family: container.find('.ast-typography-family').val(),
                    weight: container.find('.ast-typography-weight').val(),
                    style: container.find('.ast-typography-style').val(),
                    transform: container.find('.ast-typography-transform').val(),
                    size: {},
                    line_height: container.find('.ast-typography-line-height').val(),
                    letter_spacing: container.find('.ast-typography-letter-spacing').val()
                };
                container.find('.ast-typography-size').each(function() {
                    val.size[$(this).data('device')] = parseFloat($(this).val()) || 16;
                });
                hidden.val(JSON.stringify(val)).trigger('change');
                control.setting.set(val);
            }

            container.on('change input', 'select, input', update);
        }
    });
})(jQuery);
