(function ($) {
    'use strict';

    var HistoryEngine = {
        container: null,
        undoBtn: null,
        redoBtn: null,
        clearBtn: null,
        entries: []
    };

    function init() {
        HistoryEngine.container = document.getElementById('vc-history-timeline');
        HistoryEngine.undoBtn = document.getElementById('vc-history-undo');
        HistoryEngine.redoBtn = document.getElementById('vc-history-redo');
        HistoryEngine.clearBtn = document.getElementById('vc-history-clear');

        loadTimeline();
        bindEvents();
    }

    function loadTimeline() {
        if (!HistoryEngine.container) return;

        $.ajax({
            url: PhantomHistory.restUrl + '/history',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomHistory.nonce);
            },
            success: function (resp) {
                HistoryEngine.entries = resp.history || [];
                renderTimeline();
            },
            error: function () {
                HistoryEngine.container.innerHTML =
                    '<div class="vc-history-empty">Failed to load history.</div>';
            }
        });
    }

    function renderTimeline() {
        if (!HistoryEngine.container) return;

        if (HistoryEngine.entries.length === 0) {
            HistoryEngine.container.innerHTML =
                '<div class="vc-history-empty">No history yet.</div>';
            return;
        }

        var html = '<div class="vc-history-list">';
        HistoryEngine.entries.slice(0, 50).forEach(function (entry, index) {
            var action = entry.action || 'manual';
            var timestamp = entry.timestamp || '';
            var component = entry.component || '';
            var property = entry.property || '';
            var instanceId = entry.instance_id || '';
            var isCurrent = index === 0;

            var timeDisplay = '';
            if (timestamp) {
                var d = new Date(timestamp.replace(' ', 'T') + 'Z');
                if (!isNaN(d.getTime())) {
                    var diff = Math.floor((Date.now() - d.getTime()) / 1000);
                    if (diff < 60) timeDisplay = 'just now';
                    else if (diff < 3600) timeDisplay = Math.floor(diff / 60) + 'm ago';
                    else if (diff < 86400) timeDisplay = Math.floor(diff / 3600) + 'h ago';
                    else timeDisplay = Math.floor(diff / 86400) + 'd ago';
                }
            }

            html += '<div class="vc-history-entry' + (isCurrent ? ' is-current' : '') + '" data-instance-id="' + escapeHtml(instanceId) + '" data-action="' + escapeHtml(action) + '" data-index="' + index + '">';

            html += '<div class="vc-history-marker"></div>';

            html += '<div class="vc-history-content">';
            html += '<span class="vc-history-badge vc-history-badge--' + escapeHtml(action) + '">' + escapeHtml(action) + '</span>';
            html += '<span class="vc-history-desc">';
            if (component) {
                html += '<strong>' + escapeHtml(component) + '</strong>';
                if (property && property !== 'snapshot') {
                    html += ' → ' + escapeHtml(property);
                }
            } else {
                html += '<em>Snapshot</em>';
            }
            html += '</span>';
            html += '<span class="vc-history-time">' + timeDisplay + '</span>';
            html += '</div>';

            html += '</div>';
        });
        html += '</div>';
        HistoryEngine.container.innerHTML = html;
    }

    function historyUndo() {
        $.ajax({
            url: PhantomHistory.restUrl + '/history/undo',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomHistory.nonce);
            },
            success: function (resp) {
                showNotice('Undo: ' + (resp.entry ? resp.entry.action : 'done'), 'info');
                refreshAfterHistory();
            },
            error: function () {
                showNotice('Nothing to undo.', 'info');
            }
        });
    }

    function historyRedo() {
        $.ajax({
            url: PhantomHistory.restUrl + '/history/redo',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomHistory.nonce);
            },
            success: function (resp) {
                showNotice('Redo: ' + (resp.entry ? resp.entry.action : 'done'), 'info');
                refreshAfterHistory();
            },
            error: function () {
                showNotice('Nothing to redo.', 'info');
            }
        });
    }

    function historyClear() {
        if (!confirm('Clear all history? This cannot be undone.')) return;

        $.ajax({
            url: PhantomHistory.restUrl + '/history/clear',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomHistory.nonce);
            },
            success: function () {
                HistoryEngine.entries = [];
                renderTimeline();
                showNotice('History cleared.', 'info');
            },
            error: function () {
                showNotice('Failed to clear history.', 'error');
            }
        });
    }

    function revertToEntry(index) {
        var entry = HistoryEngine.entries[index];
        if (!entry) return;

        $.ajax({
            url: PhantomHistory.restUrl + '/history/undo',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomHistory.nonce);
            },
            data: { target: index },
            success: function () {
                showNotice('Reverted to: ' + (entry.action || 'snapshot'), 'info');
                refreshAfterHistory();
            },
            error: function () {
                showNotice('Failed to revert.', 'error');
            }
        });
    }

    function refreshAfterHistory() {
        loadTimeline();
        if (window.PhantomComponentTree && typeof window.PhantomComponentTree.refresh === 'function') {
            window.PhantomComponentTree.refresh();
        }
        if (window.VC && window.VC.selectedComponent && typeof window.renderInspector === 'function') {
            window.renderInspector(window.VC.selectedComponent, window.VC.selectedInstance);
        }
    }

    function bindEvents() {
        if (HistoryEngine.undoBtn) {
            HistoryEngine.undoBtn.addEventListener('click', historyUndo);
        }
        if (HistoryEngine.redoBtn) {
            HistoryEngine.redoBtn.addEventListener('click', historyRedo);
        }
        if (HistoryEngine.clearBtn) {
            HistoryEngine.clearBtn.addEventListener('click', historyClear);
        }

        if (HistoryEngine.container) {
            HistoryEngine.container.addEventListener('click', function (e) {
                var entry = e.target.closest('.vc-history-entry');
                if (entry) {
                    var index = parseInt(entry.dataset.index, 10);
                    if (!isNaN(index) && index > 0) {
                        revertToEntry(index);
                    }
                }
            });
        }

        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                historyUndo();
            }
            if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                e.preventDefault();
                historyRedo();
            }
        });
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function showNotice(message, type) {
        if (typeof window.showNotice === 'function') {
            window.showNotice(message, type);
            return;
        }
        var types = {
            success: '#edfaef',
            error: '#fcf0f1',
            warning: '#fef8ee',
            info: '#f0f6fc'
        };
        var colors = {
            success: '#2c6b3e',
            error: '#b32d2e',
            warning: '#996800',
            info: '#2271b1'
        };
        var notice = $('<div class="vc-notice" style="position:fixed;bottom:16px;right:16px;padding:12px 16px;border-radius:4px;font-size:13px;z-index:99999;background:' + (types[type] || '#fff') + ';border-left:4px solid ' + (colors[type] || '#2271b1') + ';box-shadow:0 2px 8px rgba(0,0,0,0.15);max-width:350px;">' + message + '</div>');
        $('body').append(notice);
        setTimeout(function () {
            notice.fadeOut(300, function () { notice.remove(); });
        }, 3000);
    }

    $(document).ready(init);

    window.PhantomHistory = {
        undo: historyUndo,
        redo: historyRedo,
        clear: historyClear,
        refresh: loadTimeline
    };
})(jQuery);
