(function($) {
    'use strict';

    wp.customize.controlConstructor['ast-preset-card'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;
            control.container.on('click', '.ast-preset-card', function() {
                var item = $(this);
                var slug = item.data('preset-slug');
                if (!slug) {
                    return;
                }
                control.container.find('.ast-preset-card').removeClass('ast-preset-card--active').css('border-color', '#ddd');
                item.addClass('ast-preset-card--active').css('border-color', '#2271b1');
                control.container.find('.ast-preset-card-input').val(slug).trigger('change');
                control.setting.set(slug);
            });
            control.setting.bind(function(value) {
                control.params.value = value;
                control.renderContent();
            });
        }
    });
})(jQuery);
