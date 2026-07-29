(function($) {
    'use strict';
    wp.customize.controlConstructor['ast-toggle'] = wp.customize.Control.extend({
        ready: function() {
            var control = this;
            control.container.on('change', '.ast-toggle-input', function() {
                control.setting.set(this.checked ? '1' : '');
            });
            control.setting.bind(function(value) {
                control.params.value = value;
                control.renderContent();
            });
        }
    });
})(jQuery);
