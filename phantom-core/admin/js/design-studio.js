(function ($) {
    'use strict';

    $(document).on('click', '.apply-preset', function () {
        var button = $(this);
        var presetId = button.data('id');

        button.prop('disabled', true).text('Applying...');

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
                    button.closest('.phantom-preset-card').addClass('active');
                    button.text('Applied');
                } else {
                    button.text('Failed');
                    alert(response.data || 'Failed to apply preset.');
                }
            },
            error: function () {
                button.text('Error');
            },
            complete: function () {
                setTimeout(function () {
                    button.prop('disabled', false).text('Apply');
                }, 2000);
            }
        });
    });

})(jQuery);
