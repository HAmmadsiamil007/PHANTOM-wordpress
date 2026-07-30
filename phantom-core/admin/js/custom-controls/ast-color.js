(function($) {
    'use strict';

    wp.customize.controlConstructor['ast-color'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;
            var settingId = control.id;
            var usage = ('undefined' !== typeof PhantomTokenUsage) ? PhantomTokenUsage[settingId] : null;

            control.initPicker();
            control.renderUsageBadge(usage);

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
                control.renderUsageBadge(usage);
            });
        },

        renderUsageBadge: function(usage) {
            if (!usage || !usage.length) {
                return;
            }
            var container = this.container;
            var existing = container.find('.ast-color-usage');
            if (existing.length) {
                existing.text('Used by: ' + usage.join(', '));
                return;
            }
            var badge = $('<div class="ast-color-usage" style="font-size:10px;color:#666;margin-top:4px;line-height:1.4;">Used by: ' + usage.join(', ') + '</div>');
            container.find('.ast-color-container').after(badge);
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
