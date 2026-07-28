(function ($) {
    'use strict';

    var PhantomToast = {
        init: function () {
            if ($('#phantom-toast-container').length) return;
            $('<div id="phantom-toast-container">').css({
                position: 'fixed',
                top: '32px',
                right: '20px',
                'z-index': 100000,
                display: 'flex',
                'flex-direction': 'column',
                gap: '8px'
            }).appendTo('body');
        },
        show: function (message, type) {
            this.init();
            type = type || 'info';
            var colors = {
                success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
                error: { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
                info: { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' },
                warning: { bg: '#fff3cd', border: '#ffeeba', text: '#856404' }
            };
            var c = colors[type] || colors.info;
            var toast = $('<div class="phantom-toast">').css({
                background: c.bg,
                border: '1px solid ' + c.border,
                color: c.text,
                padding: '12px 20px',
                'border-radius': '6px',
                'box-shadow': '0 2px 8px rgba(0,0,0,0.15)',
                'font-size': '14px',
                opacity: 0,
                transform: 'translateX(100%)',
                transition: 'all 0.3s ease',
                'max-width': '400px'
            }).text(message);
            $('#phantom-toast-container').append(toast);
            setTimeout(function () {
                toast.css({ opacity: 1, transform: 'translateX(0)' });
            }, 50);
            setTimeout(function () {
                toast.css({ opacity: 0, transform: 'translateX(100%)' });
                setTimeout(function () { toast.remove(); }, 300);
            }, 3500);
        }
    };

    $(document).on('click', '.apply-preset', function () {
        var button = $(this);
        var card = button.closest('.phantom-preset-card');
        var presetId = button.data('id');
        var originalText = button.text();

        button.prop('disabled', true).html('<span class="spinner" style="display:inline-block;float:none;margin:0 4px 0 0;visibility:visible;"></span> Applying...');

        $.ajax({
            url: phantomDesign.ajaxUrl,
            method: 'POST',
            data: {
                action: 'phantom_apply_preset',
                preset_id: presetId,
                _wpnonce: phantomDesign.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('.phantom-preset-card').removeClass('active');
                    card.addClass('active');
                    button.text('Applied');
                    PhantomToast.show('Preset "' + card.find('h3').text() + '" applied successfully!', 'success');
                } else {
                    button.text(originalText);
                    var msg = response.data || 'Failed to apply preset.';
                    PhantomToast.show(msg, 'error');
                }
            },
            error: function () {
                button.text(originalText);
                PhantomToast.show('Network error. Please try again.', 'error');
            },
            complete: function () {
                setTimeout(function () {
                    button.prop('disabled', false);
                }, 2500);
            }
        });
    });

})(jQuery);