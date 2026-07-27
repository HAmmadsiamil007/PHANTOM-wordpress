(function ($) {
    'use strict';

    var modal = $('#phantom-demo-modal');
    var modalBody = $('#phantom-modal-body');
    var modalConfirm = $('#phantom-modal-confirm');
    var modalTitle = $('#phantom-modal-title');

    function showModal(title, content) {
        modalTitle.text(title);
        modalBody.html(content);
        modal.show();
    }

    function hideModal() {
        modal.hide();
    }

    function ajax(action, slug, callback) {
        $.post(phantomDemo.ajaxUrl, {
            action: action,
            slug: slug,
            nonce: phantomDemo.nonce
        }, null, 'json').done(function (response) {
            if (callback) {
                callback(response);
            }
        }).fail(function () {
            alert(phantomDemo.i18n.error);
        });
    }

    $('.activate-demo').on('click', function () {
        var slug = $(this).data('slug');
        var $btn = $(this);

        showModal(phantomDemo.i18n.activateConfirm, '<p>' + phantomDemo.i18n.checkingCompat + '</p>');
        modalConfirm.show().text(phantomDemo.i18n.activateConfirm);

        ajax('phantom_activate_precheck', slug, function (response) {
            if (response.success) {
                var checks = response.data;
                var html = '<div class="compat-checks">';
                $.each(checks.checks, function (i, check) {
                    var icon = check.status === 'pass' ? '&#10003;' : '&#10007;';
                    html += '<p>' + icon + ' ' + check.message + '</p>';
                });
                html += '</div>';
                modalBody.html(html);
            } else {
                modalBody.html('<p>' + (response.data.message || phantomDemo.i18n.error) + '</p>');
                modalConfirm.hide();
            }
        });

        modalConfirm.off('click').on('click', function () {
            ajax('phantom_activate_demo', slug, function (response) {
                if (response.success) {
                    hideModal();
                    location.reload();
                } else {
                    alert(response.data.message || phantomDemo.i18n.error);
                }
            });
        });
    });

    $('.deactivate-demo').on('click', function () {
        if (!confirm(phantomDemo.i18n.deactivateConfirm)) {
            return;
        }

        ajax('phantom_deactivate_demo', '', function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || phantomDemo.i18n.error);
            }
        });
    });

    $('.delete-demo').on('click', function () {
        var slug = $(this).data('slug');

        if (!confirm(phantomDemo.i18n.deleteConfirm)) {
            return;
        }

        ajax('phantom_delete_demo', slug, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || phantomDemo.i18n.error);
            }
        });
    });

    $('.phantom-modal-close, .phantom-modal-cancel, .phantom-modal-backdrop').on('click', hideModal);

})(jQuery);
