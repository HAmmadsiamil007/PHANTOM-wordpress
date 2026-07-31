(function ($) {
    'use strict';

    var VC = {
        previewFrame: null,
        selectedComponent: null,
        selectedInstance: null,
        currentState: 'normal',
        viewport: 'desktop',
        isDirty: false,
        pendingChanges: {},
        favorites: []
    };

    function init() {
        VC.previewFrame = document.getElementById('vc-preview-iframe');

        setupMessageListener();
        setupViewportButtons();
        setupSaveButton();
        setupPanelToggles();
        setupDevTools();
        setupKeyboardShortcuts();
        setupSearch();
        setupFavorites();
        setupLockToggle();

        var loader = document.getElementById('vc-preview-loader');
        if (VC.previewFrame) {
            VC.previewFrame.addEventListener('load', function () {
                if (loader) loader.style.display = 'none';
                notifyFrameState();
            });
        }

        updateBuildStatus();
        loadFavorites();
        loadComponentTree();
    }

    function notifyFrameState() {
        if (VC.previewFrame && VC.previewFrame.contentWindow) {
            VC.previewFrame.contentWindow.postMessage({
                type: 'vc-state-change',
                state: VC.currentState,
                viewport: VC.viewport
            }, '*');
        }
    }

    function setupMessageListener() {
        window.addEventListener('message', function (e) {
            if (!e.data || !e.data.type) return;

            switch (e.data.type) {
                case 'vc-element-selected':
                    onElementSelected(e.data.data);
                    break;
                case 'vc-element-locked':
                    onElementLocked(e.data.data);
                    break;
                case 'vc-engine-ready':
                    console.log('[VC] Selection engine ready');
                    break;
            }
        });
    }

    function onElementSelected(data) {
        VC.selectedComponent = data.component;
        VC.selectedInstance = data.instance || data.component + '.' + Date.now();
        VC.currentState = data.state || 'normal';

        showSidebarContent();
        renderInspector(data.component, VC.selectedInstance);
    }

    function onElementLocked(data) {
        showNotice('This element is locked and cannot be edited.', 'warning');
    }

    function showSidebarContent() {
        var empty = document.getElementById('vc-sidebar-empty');
        var content = document.getElementById('vc-sidebar-content');
        if (empty) empty.style.display = 'none';
        if (content) content.style.display = 'block';
    }

    function renderInspector(componentName, instanceId) {
        var panelsContainer = document.getElementById('vc-sidebar-panels');
        var headerContainer = document.getElementById('vc-sidebar-header');
        if (!panelsContainer || !headerContainer) return;

        panelsContainer.innerHTML = '<div class="vc-panel-loading"><span class="spinner is-active"></span> Loading...</div>';

        $.ajax({
            url: PhantomVC.restUrl + '/components/' + encodeURIComponent(componentName) + '/inspector',
            method: 'GET',
            data: {
                state: VC.currentState,
                viewport: VC.viewport
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                if (resp.success && resp.data) {
                    if (resp.data.panels) {
                        panelsContainer.innerHTML = resp.data.panels;
                    }
                    bindControls();
                    renderInspectorHeader(componentName, instanceId);
                }
            },
            error: function () {
                panelsContainer.innerHTML = '<div class="vc-panel-error">Failed to load inspector. Check REST API.</div>';
            }
        });
    }

    function renderInspectorHeader(componentName, instanceId) {
        var header = document.getElementById('vc-sidebar-header');
        if (!header) return;

        var isFav = VC.favorites.indexOf('instance:' + instanceId) !== -1;
        var favClass = isFav ? ' active' : '';

        header.innerHTML =
            '<div class="vc-header-title">' +
                '<span class="dashicons dashicons-layout"></span>' +
                '<h3>' + componentName + '</h3>' +
                '<button type="button" class="vc-fav-btn' + favClass + '" data-type="instance" data-id="' + instanceId + '" title="Toggle favorite">' +
                    '<span class="dashicons dashicons-star-filled"></span>' +
                '</button>' +
                '<button type="button" class="vc-lock-toggle" data-id="' + instanceId + '" title="Toggle lock">' +
                    '<span class="dashicons dashicons-lock"></span>' +
                '</button>' +
            '</div>';
    }

    function bindControls() {
        bindColorPickers();
        bindRangeControls();
        bindSelectControls();
        bindTextInputs();
        bindAssetButtons();
        bindStateButtons();
        bindViewportOptions();
        bindPanelToggles();
        bindFavButtons();
    }

    function bindColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.vc-color-picker').wpColorPicker({
                change: function (event, ui) {
                    var input = $(this);
                    var property = input.data('property');
                    var value = ui.color.toString();
                    input.closest('.vc-color-picker-wrapper').find('.vc-color-swatch').css('background', value);
                    queueChange(property, value);
                }
            });
        } else {
            $('.vc-color-picker').on('input', function () {
                var input = $(this);
                var property = input.data('property');
                var value = input.val();
                input.closest('.vc-color-picker-wrapper').find('.vc-color-swatch').css('background', value);
                queueChange(property, value);
            });
        }

        $('.vc-color-swatch').on('click', function () {
            $(this).closest('.vc-color-picker-wrapper').find('.vc-color-picker').click();
        });
    }

    function bindRangeControls() {
        $('.vc-range').on('input', function () {
            var val = $(this).val();
            $(this).closest('.vc-range-wrapper').find('.vc-range-value').val(val);
            var property = $(this).data('property');
            queueChange(property, val);
        });

        $('.vc-range-value').on('input', function () {
            var val = $(this).val();
            $(this).closest('.vc-range-wrapper').find('.vc-range').val(val);
            var property = $(this).data('property');
            queueChange(property, val);
        });
    }

    function bindSelectControls() {
        $('.vc-select, .vc-font-picker').on('change', function () {
            var property = $(this).data('property');
            var value = $(this).val();
            queueChange(property, value);
        });
    }

    function bindTextInputs() {
        $('.vc-text-input').on('change', function () {
            var property = $(this).data('property') || $(this).data('setting');
            var value = $(this).val();
            queueChange(property, value);
        });
    }

    function bindAssetButtons() {
        $('.vc-btn-upload').on('click', function () {
            var assetKey = $(this).data('asset');
            openMediaUploader(assetKey);
        });

        $('.vc-btn-reset').on('click', function () {
            var assetKey = $(this).data('asset');
            resetAsset(assetKey);
        });
    }

    function bindStateButtons() {
        $(document).on('click', '.vc-state-btn', function () {
            $('.vc-state-btn').removeClass('active');
            $(this).addClass('active');
            VC.currentState = $(this).data('state');
            notifyFrameState();
            if (VC.selectedComponent) {
                renderInspector(VC.selectedComponent, VC.selectedInstance);
            }
        });
    }

    function bindViewportOptions() {
        $(document).on('click', '.vc-viewport-option', function () {
            var vp = $(this).data('viewport');
            $('.vc-viewport-option').removeClass('active');
            $(this).addClass('active');
            VC.viewport = vp;

            var label = $(this).find('span:eq(0)').text();
            $('.vc-viewport-label').text(label);

            $('.vc-viewport-indicator-btn .dashicons').attr('class', 'dashicons ' + $(this).find('.dashicons').attr('class'));

            $('.vc-viewport-dropdown').removeClass('open');

            if (VC.previewFrame) {
                var width = '100%';
                if (vp === 'tablet') width = '768px';
                else if (vp === 'mobile') width = '375px';
                VC.previewFrame.style.width = width;
                VC.previewFrame.style.margin = '0 auto';
            }

            notifyFrameState();

            if (VC.selectedComponent) {
                renderInspector(VC.selectedComponent, VC.selectedInstance);
            }
        });

        $(document).on('click', '.vc-viewport-indicator-btn', function () {
            $(this).closest('.vc-viewport-dropdown-wrapper').find('.vc-viewport-dropdown').toggleClass('open');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.vc-viewport-dropdown-wrapper').length) {
                $('.vc-viewport-dropdown').removeClass('open');
            }
        });
    }

    function bindPanelToggles() {
        $('.vc-panel-header').off('click').on('click', function () {
            $(this).closest('.vc-panel').toggleClass('collapsed');
        });
    }

    function queueChange(property, value) {
        VC.isDirty = true;
        VC.pendingChanges[property] = value;

        clearTimeout(window._vcSaveTimer);
        window._vcSaveTimer = setTimeout(function () {
            applyLiveChanges();
        }, 300);

        updateSaveButton();
    }

    function applyLiveChanges() {
        if (Object.keys(VC.pendingChanges).length === 0) return;

        var cssVars = {};
        var stateSuffix = VC.currentState !== 'normal' ? ':' + VC.currentState : '';
        var vpSuffix = VC.viewport !== 'desktop' ? '@' + VC.viewport : '';

        for (var prop in VC.pendingChanges) {
            if (VC.pendingChanges.hasOwnProperty(prop)) {
                var varName = '--' + prop.replace(/\./g, '-');
                if (stateSuffix) varName += stateSuffix;
                if (vpSuffix) varName += vpSuffix;
                cssVars[varName] = VC.pendingChanges[prop];
            }
        }

        if (VC.previewFrame && VC.previewFrame.contentWindow) {
            VC.previewFrame.contentWindow.postMessage({
                type: 'vc-apply-css',
                cssVars: cssVars,
                state: VC.currentState,
                viewport: VC.viewport
            }, '*');
        }
    }

    function openMediaUploader(assetKey) {
        if (typeof wp !== 'undefined' && wp.media) {
            var frame = wp.media({
                title: 'Select Asset',
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                updateAsset(assetKey, attachment.id, attachment.url);
            });

            frame.open();
        }
    }

    function updateAsset(assetKey, attachmentId, url) {
        queueChange('asset_' + assetKey, attachmentId);

        var preview = $('[data-asset="' + assetKey + '"]').find('.vc-asset-preview');
        if (preview.length) {
            preview.html('<img src="' + url + '" alt="" style="max-width:100%;max-height:80px;object-fit:cover;" />');
        }
    }

    function resetAsset(assetKey) {
        queueChange('asset_' + assetKey, '');
        var preview = $('[data-asset="' + assetKey + '"]').find('.vc-asset-preview');
        if (preview.length) {
            preview.html('<span style="color:#a7aaad;font-size:11px;">Default</span>');
        }
    }

    function setupViewportButtons() {
        $('.vc-viewport-btn').on('click', function () {
            $('.vc-viewport-btn').removeClass('active');
            $(this).addClass('active');
            VC.viewport = $(this).data('viewport');

            if (VC.previewFrame) {
                var width = '100%';
                if (VC.viewport === 'tablet') width = '768px';
                else if (VC.viewport === 'mobile') width = '375px';
                VC.previewFrame.style.width = width;
                VC.previewFrame.style.margin = '0 auto';
            }

            notifyFrameState();
        });
    }

    function setupSaveButton() {
        $('#vc-save-changes').on('click', function () {
            saveChanges();
        });

        $(document).on('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveChanges();
            }
        });
    }

    function saveChanges() {
        if (Object.keys(VC.pendingChanges).length === 0) {
            showNotice('No changes to save.', 'info');
            return;
        }

        var btn = $('#vc-save-changes');
        btn.prop('disabled', true).html('<span class="spinner is-active" style="margin:0"></span> Saving...');

        $.ajax({
            url: PhantomVC.restUrl + '/settings',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
                xhr.setRequestHeader('X-Phantom-Nonce', PhantomVC.nonce);
            },
            data: {
                settings: VC.pendingChanges,
                instance: VC.selectedInstance,
                component: VC.selectedComponent,
                state: VC.currentState,
                viewport: VC.viewport
            },
            success: function (resp) {
                VC.isDirty = false;
                VC.pendingChanges = {};
                updateSaveButton();

                recordHistorySnapshot('save');

                $.ajax({
                    url: PhantomVC.restUrl + '/publish',
                    method: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
                        xhr.setRequestHeader('X-Phantom-Nonce', PhantomVC.nonce);
                    },
                    data: { profile: 'production' },
                    success: function (buildResp) {
                        var version = buildResp.version || '';
                        var msg = version
                            ? 'Published! Build: ' + version.substring(0, 7)
                            : 'Published successfully!';
                        showNotice(msg, 'success');
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Published');
                        setTimeout(function () {
                            btn.html('<span class="dashicons dashicons-yes"></span> Publish');
                        }, 2000);
                        updateBuildStatus();
                    },
                    error: function () {
                        showNotice('Settings saved, but CSS build failed.', 'warning');
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Published (no CSS)');
                        setTimeout(function () {
                            btn.html('<span class="dashicons dashicons-yes"></span> Publish');
                        }, 2000);
                    }
                });
            },
            error: function (jqXHR) {
                var msg = 'Failed to save changes.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    msg = jqXHR.responseJSON.message;
                }
                showNotice(msg, 'error');
                btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Retry');
            }
        });
    }

    function updateSaveButton() {
        var btn = $('#vc-save-changes');
        var count = Object.keys(VC.pendingChanges).length;
        if (count > 0) {
            btn.html('<span class="dashicons dashicons-yes"></span> Publish (' + count + ')');
            btn.css('background', '#d63638');
        } else {
            btn.html('<span class="dashicons dashicons-yes"></span> Publish');
            btn.css('background', '');
        }
    }

    function showNotice(message, type) {
        var types = {
            success: '#edfaef',
            error: '#fcf0f1',
            warning: '#fef8ee',
            info: '#f0f6fc'
        };

        var notice = $('<div class="vc-notice" style="position:fixed;bottom:16px;right:16px;padding:12px 16px;border-radius:4px;font-size:13px;z-index:99999;background:' + (types[type] || '#fff') + ';border-left:4px solid ' + (type === 'success' ? '#2c6b3e' : type === 'error' ? '#b32d2e' : type === 'warning' ? '#996800' : '#2271b1') + ';box-shadow:0 2px 8px rgba(0,0,0,0.15);max-width:350px;">' + message + '</div>');
        $('body').append(notice);

        setTimeout(function () {
            notice.fadeOut(300, function () { notice.remove(); });
        }, 3000);
    }

    function updateBuildStatus() {
        $.ajax({
            url: PhantomVC.restUrl + '/build/status',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                var statusEl = $('#vc-build-status');
                if (!statusEl.length) return;

                if (resp.current) {
                    var version = resp.version ? resp.version.substring(0, 7) : '---';
                    statusEl.html(
                        '<span class="vc-build-version" title="Build #' + resp.build + '">' +
                        'v' + version +
                        '</span>' +
                        '<span class="vc-build-date">' + (resp.date || '') + '</span>'
                    );
                } else {
                    statusEl.html('<span class="vc-build-none">Not built</span>');
                }
            }
        });
    }

    function setupPanelToggles() {
        $(document).on('click', '.vc-panel-header', function () {
            $(this).closest('.vc-panel').toggleClass('collapsed');
        });
    }

    // ===== Phase 4: Global Search =====

    function setupSearch() {
        if (document.getElementById('vc-global-search')) {
            // PHP-rendered search bar already present (Search_UI::render_search_bar);
            // bind only the input handler + outside-click close, no duplicate injection.
            bindSearchInput('#vc-global-search');
            bindSearchOutsideClick();
            return;
        }

        var toolbar = $('.vc-toolbar-right');
        var searchHtml =
            '<div class="vc-search-wrapper">' +
                '<span class="dashicons dashicons-search vc-search-icon"></span>' +
                '<input type="text" class="vc-search-input" id="vc-global-search" placeholder="Search components, instances..." />' +
                '<div class="vc-search-results" id="vc-search-results"></div>' +
            '</div>';
        toolbar.prepend(searchHtml);

        bindSearchInput('.vc-search-input');
        bindSearchOutsideClick();
    }

    function bindSearchInput(selector) {
        var debounceTimer;
        $(selector).on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(debounceTimer);

            if (q.length < 2) {
                $('#vc-search-results').removeClass('open').empty();
                return;
            }

            debounceTimer = setTimeout(function () {
                performSearch(q);
            }, 250);
        });
    }

    function bindSearchOutsideClick() {
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.vc-search-wrapper').length) {
                $('#vc-search-results').removeClass('open').empty();
            }
        });
    }

    function performSearch(q) {
        $.ajax({
            url: PhantomVC.restUrl + '/search',
            method: 'GET',
            data: { q: q },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                var resultsEl = $('#vc-search-results');
                resultsEl.empty();

                if (!resp.results || resp.results.length === 0) {
                    resultsEl.html('<div class="vc-search-empty">No results found.</div>');
                    resultsEl.addClass('open');
                    return;
                }

                var categoryLabels = {
                    component: 'Components',
                    instance: 'Instances',
                    property: 'Properties',
                    token: 'Design Tokens',
                    asset: 'Assets',
                    animation: 'Animations',
                    setting: 'Settings'
                };
                var grouped = {};
                resp.results.forEach(function (item) {
                    var cat = item.type || 'general';
                    if (!grouped[cat]) grouped[cat] = [];
                    grouped[cat].push(item);
                });

                Object.keys(grouped).forEach(function (cat) {
                    var label = categoryLabels[cat] || cat.charAt(0).toUpperCase() + cat.slice(1) + 's';
                    resultsEl.append('<div class="vc-search-category">' + label + '</div>');
                    grouped[cat].forEach(function (item) {
                        var itemEl = $(
                            '<div class="vc-search-item" data-type="' + item.type + '" data-id="' + item.id + '">' +
                                '<span class="vc-search-item-label">' + item.label + '</span>' +
                                '<span class="vc-search-item-desc">' + (item.description || '') + '</span>' +
                            '</div>'
                        );
                        itemEl.on('click', function () {
                            onSearchSelect(item);
                        });
                        resultsEl.append(itemEl);
                    });
                });

                resultsEl.addClass('open');
            }
        });
    }

    function onSearchSelect(item) {
        $('#vc-search-results').removeClass('open').empty();
        $('.vc-search-input').val('');

        if (item.type === 'component') {
            VC.selectedComponent = item.id;
            VC.selectedInstance = item.id + '.' + Date.now();
            showSidebarContent();
            renderInspector(item.id, VC.selectedInstance);
        } else if (item.type === 'instance') {
            VC.selectedComponent = item.type === 'instance'
                ? item.id.split(/[.\-]/)[0]
                : item.id;
            VC.selectedInstance = item.id;
            showSidebarContent();
            renderInspector(VC.selectedComponent, VC.selectedInstance);
        }
    }

    // ===== Phase 4: Favorites =====

    function setupFavorites() {
        $(document).on('click', '.vc-fav-btn', function () {
            var btn = $(this);
            var type = btn.data('type');
            var id = btn.data('id');
            toggleFavorite(type, id, btn);
        });
    }

    function toggleFavorite(type, id, btn) {
        $.ajax({
            url: PhantomVC.restUrl + '/favorites/toggle',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            data: { type: type, id: id },
            success: function (resp) {
                if (resp.success) {
                    var isActive = resp.favorite.active;
                    var key = type + ':' + id;
                    if (isActive) {
                        if (VC.favorites.indexOf(key) === -1) VC.favorites.push(key);
                    } else {
                        VC.favorites = VC.favorites.filter(function (f) { return f !== key; });
                    }
                    if (btn) {
                        btn.toggleClass('active', isActive);
                    }
                    showNotice(isActive ? 'Added to favorites' : 'Removed from favorites', 'info');
                    loadComponentTree();
                }
            }
        });
    }

    function loadFavorites() {
        $.ajax({
            url: PhantomVC.restUrl + '/favorites',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                VC.favorites = [];
                if (resp.favorites) {
                    resp.favorites.forEach(function (fav) {
                        VC.favorites.push(fav.type + ':' + fav.id);
                    });
                }
            }
        });
    }

    function bindFavButtons() {
        $('.vc-fav-btn').off('click').on('click', function () {
            var btn = $(this);
            var type = btn.data('type');
            var id = btn.data('id');
            toggleFavorite(type, id, btn);
        });
    }

    // ===== Phase 4: Lock System =====

    function setupLockToggle() {
        $(document).on('click', '.vc-lock-toggle', function () {
            var btn = $(this);
            var id = btn.data('id');
            if (!id) return;

            var isLocked = btn.hasClass('locked');
            var endpoint = isLocked ? '/instances/unlock' : '/instances/lock';

            $.ajax({
                url: PhantomVC.restUrl + endpoint,
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
                },
                data: { id: id },
                success: function (resp) {
                    btn.toggleClass('locked', resp.locked);
                    showNotice(
                        resp.locked ? 'Instance locked' : 'Instance unlocked',
                        'info'
                    );
                    loadComponentTree();
                },
                error: function () {
                    showNotice('Failed to toggle lock.', 'error');
                }
            });
        });
    }

    // ===== Phase 4: History =====

    function setupKeyboardShortcuts() {
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

    function recordHistorySnapshot(action) {
        $.ajax({
            url: PhantomVC.restUrl + '/history/snapshot',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            data: { action: action || 'manual' },
            success: function () {
                loadHistoryTimeline();
            }
        });
    }

    function historyUndo() {
        $.ajax({
            url: PhantomVC.restUrl + '/history/undo',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
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
            url: PhantomVC.restUrl + '/history/redo',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
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

    function refreshAfterHistory() {
        if (VC.selectedComponent) {
            renderInspector(VC.selectedComponent, VC.selectedInstance);
        }
        loadComponentTree();
    }

    function loadHistoryTimeline() {
        var container = document.getElementById('vc-history-timeline');
        if (!container) return;

        $.ajax({
            url: PhantomVC.restUrl + '/history',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                container.innerHTML = '';
                if (resp.history && resp.history.length > 0) {
                    resp.history.slice(0, 20).forEach(function (entry) {
                        var el = document.createElement('div');
                        el.className = 'vc-history-entry';
                        el.innerHTML =
                            '<span class="vc-history-action">' + entry.action + '</span>' +
                            '<span class="vc-history-time">' + entry.timestamp + '</span>';
                        container.appendChild(el);
                    });
                } else {
                    container.innerHTML = '<div class="vc-history-empty">No history yet.</div>';
                }
            }
        });
    }

    // ===== Phase 4: Component Tree =====

    function loadComponentTree() {
        $.ajax({
            url: PhantomVC.restUrl + '/instances/tree',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                renderTree(resp.tree);
                loadLockedInstances();
            }
        });
    }

    function renderTree(treeData) {
        var container = $('#vc-instance-tree');
        if (!container.length) return;

        container.empty();

        if (!treeData || treeData.length === 0) {
            container.html('<div class="vc-tree-empty">No instances yet. Click elements in preview to create them.</div>');
            return;
        }

        var html = '<ul class="vc-tree">';
        treeData.forEach(function (node) {
            html += renderTreeNode(node);
        });
        html += '</ul>';
        container.html(html);

        $('.vc-tree-toggle').on('click', function () {
            $(this).closest('.vc-tree-node').toggleClass('collapsed');
        });

        $('.vc-tree-node-label').on('click', function () {
            var id = $(this).closest('.vc-tree-node').data('instance-id');
            var component = $(this).closest('.vc-tree-node').data('component');
            if (id && component) {
                VC.selectedComponent = component;
                VC.selectedInstance = id;
                showSidebarContent();
                renderInspector(component, id);
            }
        });
    }

    function renderTreeNode(node) {
        if (!node) return '';

        var hasChildren = node.children && node.children.length > 0;
        var toggleBtn = hasChildren
            ? '<span class="vc-tree-toggle dashicons dashicons-arrow-down"></span>'
            : '<span class="vc-tree-toggle vc-tree-toggle--empty"></span>';

        var isFav = VC.favorites.indexOf('instance:' + node.id) !== -1;
        var favStar = isFav
            ? '<span class="vc-tree-fav dashicons dashicons-star-filled" title="Favorite"></span>'
            : '';

        var lockIcon = node.locked
            ? '<span class="vc-tree-locked dashicons dashicons-lock" title="Locked"></span>'
            : '';

        var badges = '';
        if (node.has_state_overrides) badges += '<span class="vc-tree-badge vc-badge-state" title="Has state overrides">S</span>';
        if (node.has_viewport_overrides) badges += '<span class="vc-tree-badge vc-badge-viewport" title="Has viewport overrides">V</span>';
        if (node.override_count > 0) badges += '<span class="vc-tree-badge vc-badge-modified" title="Modified">' + node.override_count + '</span>';

        var html = '<li class="vc-tree-node' + (node.locked ? ' is-locked' : '') + '" data-instance-id="' + node.id + '" data-component="' + node.component + '">';
        html += '<div class="vc-tree-node-label">' + toggleBtn + favStar + lockIcon + ' <span>' + node.label + '</span> ' + badges + '</div>';

        if (hasChildren) {
            html += '<ul class="vc-tree-children">';
            node.children.forEach(function (child) {
                html += renderTreeNode(child);
            });
            html += '</ul>';
        }

        html += '</li>';
        return html;
    }

    function loadLockedInstances() {
        var container = $('#vc-locked-list');
        if (!container.length) return;

        $.ajax({
            url: PhantomVC.restUrl + '/instances/locked',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                container.empty();
                if (resp.instances && resp.instances.length > 0) {
                    resp.instances.forEach(function (inst) {
                        var el = document.createElement('div');
                        el.className = 'vc-locked-item';
                        el.innerHTML =
                            '<span class="dashicons dashicons-lock"></span> ' +
                            '<span class="vc-locked-name">' + inst.component + '</span>' +
                            '<button type="button" class="vc-btn-unlock" data-id="' + inst.id + '">Unlock</button>';
                        el.querySelector('.vc-btn-unlock').addEventListener('click', function () {
                            $.ajax({
                                url: PhantomVC.restUrl + '/instances/unlock',
                                method: 'POST',
                                beforeSend: function (xhr) {
                                    xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
                                },
                                data: { id: inst.id },
                                success: function () {
                                    showNotice('Instance unlocked.', 'info');
                                    loadComponentTree();
                                }
                            });
                        });
                        container.append(el);
                    });
                } else {
                    container.html('<div class="vc-locked-empty">No locked instances.</div>');
                }
            }
        });
    }

    function setupDevTools() {
        $('#vc-dev-toggle').on('click', function () {
            var tree = $('#vc-dev-tree');
            var isVisible = tree.is(':visible');
            tree.slideToggle();
            $(this).text(isVisible ? 'Show' : 'Hide');
        });

        $('.vc-dev-node').on('click', function () {
            var component = $(this).closest('[data-component]').data('component');
            if (component) {
                $.ajax({
                    url: PhantomVC.restUrl + '/components/' + encodeURIComponent(component) + '/inspector',
                    method: 'GET',
                    data: {
                        state: VC.currentState,
                        viewport: VC.viewport
                    },
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
                    },
                    success: function (resp) {
                        if (resp.success && resp.data) {
                            showSidebarContent();
                            var panelsContainer = document.getElementById('vc-sidebar-panels');
                            if (panelsContainer && resp.data.panels) {
                                panelsContainer.innerHTML = resp.data.panels;
                            }
                            bindControls();
                        }
                    }
                });
            }
        });

        $('#vc-history-undo').on('click', function () { historyUndo(); });
        $('#vc-history-redo').on('click', function () { historyRedo(); });

        $.ajax({
            url: PhantomVC.restUrl + '/build/history',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                if (resp.history && resp.history.length > 0) {
                    var container = $('#vc-dev-tree');
                    var html = '<h4 style="margin:8px 0;color:#72aee6;">Build History</h4><ul>';
                    resp.history.forEach(function (build) {
                        var activeStyle = build.active ? ' style="color:#00ba37;"' : '';
                        html += '<li' + activeStyle + '>' +
                            build.version.substring(0, 7) +
                            ' — ' + build.date +
                            ' (' + build.size + 'B)' +
                            (build.active ? ' ✓' : '') +
                            '</li>';
                    });
                    html += '</ul>';
                    container.append(html);
                }
            }
        });
    }

    $(document).ready(init);
})(jQuery);
