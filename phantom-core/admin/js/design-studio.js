(function ($) {
    'use strict';

    var root = $('#phantom-design-studio-root');
    var restUrl = root.data('rest-url');
    var nonce = root.data('nonce');
    var pendingSave = null;

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

    var commonFonts = [
        'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
        'Poppins', 'Space Grotesk', 'DM Sans', 'Playfair Display',
        'Georgia', 'Times New Roman', 'Courier New', 'Arial', 'Helvetica'
    ];

    function buildEditor(currentValue, type, tokenName) {
        var editor;
        switch (type) {
            case 'color':
                editor = $('<input type="color" class="phantom-inline-color" spellcheck="false">').val(currentValue || '#000000');
                break;
            case 'number':
            case 'size':
            case 'font_size':
            case 'unitless':
                editor = $('<input type="text" class="phantom-inline-number" spellcheck="false">').val(currentValue || '0');
                break;
            case 'font_family':
                editor = $('<select class="phantom-inline-select">');
                $.each(commonFonts, function (i, f) {
                    editor.append($('<option>').val(f).text(f).prop('selected', f === currentValue));
                });
                editor.append($('<option>').val('system-ui').text('System UI').prop('selected', 'system-ui' === currentValue));
                editor.append($('<option>').val('serif').text('Serif').prop('selected', 'serif' === currentValue));
                editor.append($('<option>').val('sans-serif').text('Sans-Serif').prop('selected', 'sans-serif' === currentValue));
                break;
            case 'select':
            case 'easing':
                editor = $('<input type="text" class="phantom-inline-text" spellcheck="false">').val(currentValue || '');
                break;
            case 'duration':
                editor = $('<input type="text" class="phantom-inline-number" spellcheck="false">').val(currentValue || '0');
                break;
            case 'shadow':
                editor = $('<input type="text" class="phantom-inline-text phantom-wide" spellcheck="false">').val(currentValue || '');
                break;
            default:
                editor = $('<input type="text" class="phantom-inline-text" spellcheck="false">').val(currentValue || '');
        }
        return editor;
    }

    function saveToken(tokenName, value, cell) {
        if (pendingSave) {
            pendingSave.abort();
        }
        cell.addClass('phantom-saving');
        pendingSave = $.ajax({
            url: restUrl + '/design/tokens/' + encodeURIComponent(tokenName),
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            data: { value: value },
            success: function (resp) {
                if (resp.success) {
                    PhantomToast.show('Token "' + tokenName + '" saved', 'success');
                    updateCssPreview(resp.css);
                } else {
                    PhantomToast.show('Failed to save token', 'error');
                }
            },
            error: function () {
                PhantomToast.show('Network error saving token', 'error');
            },
            complete: function () {
                cell.removeClass('phantom-saving');
                pendingSave = null;
            }
        });
    }

    function updateCssPreview(css) {
        var preview = $('.phantom-css-preview');
        if (preview.length) {
            preview.val(css || '');
        }
        var status = $('.phantom-css-status');
        if (status.length) {
            status.text('Updated just now');
            clearTimeout(status.data('timer'));
            var t = setTimeout(function () { status.text('Live — updates on token edit'); }, 3000);
            status.data('timer', t);
        }
    }

    function fetchCssPreview() {
        $.ajax({
            url: restUrl + '/design/css',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function (resp) {
                if (resp.success) {
                    updateCssPreview(resp.css);
                }
            }
        });
    }

    function fetchPresetDetails(presetId, callback) {
        $.ajax({
            url: restUrl + '/design/presets/' + encodeURIComponent(presetId),
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function (resp) {
                if (resp.success && callback) callback(resp.preset);
            },
            error: function () {
                PhantomToast.show('Failed to load preset details', 'error');
            }
        });
    }

    function showComparisonModal(currentTokens, presetTokens, presetName) {
        var existing = $('#phantom-compare-modal');
        if (existing.length) existing.remove();

        var changed = 0;
        var rows = '';
        var allKeys = Object.keys(currentTokens).concat(Object.keys(presetTokens)).filter(function (k, i, a) {
            return a.indexOf(k) === i;
        }).sort();
        $.each(allKeys, function (i, key) {
            var cur = currentTokens[key] !== undefined ? String(currentTokens[key]) : '(not set)';
            var pre = presetTokens[key] !== undefined ? String(presetTokens[key]) : '(not set)';
            var diff = cur !== pre;
            if (diff) changed++;
            rows += '<tr class="' + (diff ? 'phantom-diff' : '') + '">'
                + '<td><code>' + $('<span>').text(key).html() + '</code></td>'
                + '<td class="phantom-value-old">' + $('<span>').text(cur).html() + '</td>'
                + '<td class="phantom-value-new">' + $('<span>').text(pre).html() + '</td>'
                + '</tr>';
        });

        var modal = $('<div id="phantom-compare-modal" class="phantom-modal-overlay">').css('display', 'flex').append(
            $('<div class="phantom-modal">').append(
                $('<div class="phantom-modal-header">').append(
                    $('<h2>').text('Preset Preview: ' + presetName),
                    $('<span class="phantom-modal-close">&times;</span>').on('click', function () { modal.remove(); })
                ),
                $('<div class="phantom-modal-body">').append(
                    $('<p>').text(changed + ' of ' + allKeys.length + ' tokens will change.'),
                    $('<div class="phantom-compare-scroll">').append(
                        $('<table class="widefat striped">').append(
                            $('<thead>').append(
                                $('<tr><th>Token</th><th>Current</th><th>New</th></tr>')
                            ),
                            $('<tbody>').append(rows)
                        )
                    )
                ),
                $('<div class="phantom-modal-footer">').append(
                    $('<button class="button">').text('Cancel').on('click', function () { modal.remove(); }),
                    $('<button class="button button-primary phantom-apply-compare">').text('Apply Preset').data('preset-id', presetName)
                )
            )
        );
        $('body').append(modal);
        modal.on('click', function (e) {
            if ($(e.target).is('.phantom-modal-overlay')) modal.remove();
        });
    }

    $(document).on('dblclick', '.phantom-token-value', function () {
        var cell = $(this);
        if (cell.find('input, select').length) return;

        var tokenName = cell.data('token');
        var type = cell.data('type') || 'string';
        var currentValue = cell.text().trim();

        var editor = buildEditor(currentValue, type, tokenName);
        cell.empty().append(editor);

        if (editor.is('input[type="color"]')) {
            editor.on('input change', function () {
                var val = $(this).val();
                saveToken(tokenName, val, cell);
            });
        } else {
            editor.on('blur', function () {
                var val = $(this).val().trim();
                cell.empty().text(val || '(empty)');
                saveToken(tokenName, val, cell);
                cell.data('type') === 'color';
            }).on('keydown', function (e) {
                if (e.key === 'Enter') {
                    $(this).trigger('blur');
                }
                if (e.key === 'Escape') {
                    cell.empty().text(currentValue);
                }
            });
        }
        editor.trigger('focus').trigger('select');
    });

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
                    fetchCssPreview();
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

    $(document).on('click', '.preview-preset', function () {
        var card = $(this).closest('.phantom-preset-card');
        var presetId = $(this).data('id');
        var presetName = card.find('h3').text();

        $.ajax({
            url: restUrl + '/design/tokens',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function (currentResp) {
                if (!currentResp.success) return;
                fetchPresetDetails(presetId, function (preset) {
                    showComparisonModal(currentResp.tokens, preset.tokens || {}, presetName);
                });
            },
            error: function () {
                PhantomToast.show('Failed to load current tokens', 'error');
            }
        });
    });

    $(document).on('click', '.phantom-apply-compare', function () {
        var presetId = $(this).data('preset-id');
        $('#phantom-compare-modal').remove();
        $('.apply-preset[data-id="' + presetId + '"]').trigger('click');
    });

    $(document).on('click', '.phantom-refresh-css', function () {
        fetchCssPreview();
        PhantomToast.show('CSS preview refreshed', 'info');
    });

    $(document).ready(function () {
        if ($('.phantom-css-preview').length) {
            fetchCssPreview();
        }
    });

})(jQuery);
