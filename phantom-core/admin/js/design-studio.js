(function ($) {
    'use strict';

    var DS = {
        state: {
            device: 'desktop',
            darkMode: false,
            preset: '',
            selectedComponent: null,
            selectedInstance: null,
            selectedBreadcrumb: null,
            historyPosition: 0,
            historyLength: 0,
            isDirty: false,
            isPublishing: false,
            components: [],
            treeData: [],
        },

        els: {},

        init: function () {
            if (!$('#phantom-ds-wrapper').length) return;

            this.els = {
                wrapper: $('#phantom-ds-wrapper'),
                iframe: $('#phantom-ds-iframe'),
                toolbar: $('#phantom-ds-toolbar'),
                navigator: $('#phantom-ds-navigator'),
                canvas: $('#phantom-ds-canvas'),
                inspector: $('#phantom-ds-inspector'),
                inspectorBody: $('#phantom-ds-inspector-body'),
                statusbar: $('#phantom-ds-statusbar'),
                tree: $('#phantom-ds-component-tree'),
                breadcrumb: $('#phantom-ds-breadcrumb'),
                search: $('#phantom-ds-search'),
                statusPage: $('#phantom-ds-status-page'),
                statusDevice: $('#phantom-ds-status-device'),
                statusDark: $('#phantom-ds-status-dark'),
                statusPreset: $('#phantom-ds-status-preset'),
                statusSave: $('#phantom-ds-status-save'),
                statusHistory: $('#phantom-ds-status-history'),
                overlay: $('#phantom-ds-overlay'),
                overlayMsg: $('#phantom-ds-overlay-message'),
                publishBtn: $('#phantom-ds-publish'),
                saveDraftBtn: $('#phantom-ds-save-draft'),
                undoBtn: $('#phantom-ds-undo'),
                redoBtn: $('#phantom-ds-redo'),
                darkModeBtn: $('#phantom-ds-dark-mode'),
                historyBtn: $('#phantom-ds-history'),
                exportBtn: $('#phantom-ds-export'),
                presetSelect: $('#phantom-ds-preset-select'),
                deviceBtns: $('.phantom-ds-device-btn'),
            };

            this.bindEvents();
            this.loadComponentTree();
            this.loadPresets();
            this.setupIframeBridge();
            this.startAutoSave();

        },

        startAutoSave: function () {
            var self = this;
            // Auto-save heartbeat: every 60 seconds, create a history snapshot
            // Only fires if there are unsaved changes (isDirty === true)
            self._autoSaveTimer = setInterval(function () {
                if (!self.state.isDirty) return;

                $.ajax({
                    url: phantomDS.restUrl + '/history/snapshot',
                    method: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                    },
                    data: {
                        action: 'auto',
                        description: 'Auto-save checkpoint',
                    },
                    success: function (resp) {
                        if (resp.success) {
                            self.els.statusHistory.text('Step ' + (resp.position.undo_count || 0) + '/' + (resp.position.undo_count || 0));
                        }
                    },
                });
            }, 60000);
        },

        bindEvents: function () {
            var self = this;

            this.els.deviceBtns.on('click', function () {
                var device = $(this).data('device');
                self.setDevice(device);
            });

            this.els.undoBtn.on('click', $.proxy(this.undo, this));
            this.els.redoBtn.on('click', $.proxy(this.redo, this));

            this.els.darkModeBtn.on('click', function () {
                self.toggleDarkMode();
            });

            this.els.presetSelect.on('change', function () {
                self.applyPreset($(this).val());
            });

            this.els.publishBtn.on('click', $.proxy(this.publish, this));
            this.els.saveDraftBtn.on('click', $.proxy(this.saveDraft, this));
            this.els.historyBtn.on('click', $.proxy(this.openHistory, this));
            this.els.exportBtn.on('click', $.proxy(this.exportDesign, this));

            this.els.search.on('input', $.proxy(this.filterTree, this));

            $(document).on('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                    e.preventDefault();
                    if (e.shiftKey) {
                        self.redo();
                    } else {
                        self.undo();
                    }
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    self.saveDraft();
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    self.els.search.trigger('focus');
                }
            });
        },

        setDevice: function (device) {
            this.state.device = device;
            this.els.deviceBtns.removeClass('active');
            this.els.deviceBtns.filter('[data-device="' + device + '"]').addClass('active');

            var widths = { desktop: '100%', tablet: '768px', mobile: '375px' };
            this.els.iframe.closest('.phantom-ds-canvas').css({
                'max-width': widths[device] || '100%',
                'margin': '0 auto',
            });

            var labels = {
                desktop: 'Desktop \u2014 1280px',
                tablet: 'Tablet \u2014 768px',
                mobile: 'Mobile \u2014 375px',
            };
            this.els.statusDevice.text(labels[device] || '');

            this.sendMessage({ type: 'DEVICE_CHANGE', device: device });
        },

        toggleDarkMode: function () {
            this.state.darkMode = !this.state.darkMode;
            this.els.darkModeBtn.toggleClass('active', this.state.darkMode);
            this.els.statusDark.text(this.state.darkMode ? 'Dark Mode' : 'Light Mode');
            this.sendMessage({ type: 'DARK_MODE_TOGGLE', enabled: this.state.darkMode });
        },

        applyPreset: function (presetId) {
            if (!presetId) return;
            var self = this;
            this.els.presetSelect.prop('disabled', true);

            $.ajax({
                url: phantomDS.restUrl + '/design/presets/apply',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                data: { id: presetId },
                success: function (resp) {
                    if (resp.success) {
                        self.state.preset = presetId;
                        self.els.statusPreset.text(presetId.charAt(0).toUpperCase() + presetId.slice(1));
                        self.setDirty(true);
                        self.sendMessage({ type: 'PRESET_APPLIED', preset: presetId });
                    }
                },
                error: function () {
                    self.showToast('Failed to apply preset', 'error');
                },
                complete: function () {
                    self.els.presetSelect.prop('disabled', false);
                },
            });
        },

        loadComponentTree: function () {
            var self = this;
            $.ajax({
                url: phantomDS.restUrl + '/design-studio/navigator',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                success: function (resp) {
                    if (resp.success && resp.tree) {
                        self.state.treeData = resp.tree;
                        self.renderTree(resp.tree);
                    }
                },
                error: function () {
                    self.els.tree.html('<div class="phantom-ds-tree-empty">' + phantomDS.strings.noComponents + '</div>');
                },
            });
        },

        loadPresets: function () {
            var self = this;
            $.ajax({
                url: phantomDS.restUrl + '/design/presets',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                success: function (resp) {
                    if (resp.success && resp.presets) {
                        var select = self.els.presetSelect;
                        select.empty();
                        select.append($('<option>').val('').text(phantomDS.strings.presets));
                        $.each(resp.presets, function (i, p) {
                            select.append($('<option>').val(p.id).text(p.name));
                        });
                    }
                },
            });
        },

        renderTree: function (treeData) {
            var self = this;
            var html = '';

            function buildTree(nodes, depth) {
                depth = depth || 0;
                $.each(nodes, function (i, node) {
                    var hasChildren = node.children && node.children.length > 0;
                    var icon = node.icon || 'dashicons-admin-page';
                    var cls = 'phantom-ds-tree-item';
                    if (hasChildren) cls += ' has-children';
                    if (depth === 0) cls += ' is-page';

                    html += '<div class="' + cls + '" style="padding-left:' + (12 + depth * 16) + 'px"';
                    if (node.component) {
                        html += ' data-component="' + escapeAttr(node.component) + '"';
                        html += ' data-instance="' + escapeAttr(node.instance || '') + '"';
                    }
                    html += '>';
                    if (hasChildren) {
                        html += '<span class="phantom-ds-tree-toggle dashicons dashicons-arrow-down"></span>';
                    } else {
                        html += '<span class="phantom-ds-tree-toggle phantom-ds-tree-toggle-spacer"></span>';
                    }
                    html += '<span class="dashicons ' + icon + '"></span>';
                    html += '<span class="phantom-ds-tree-label">' + escapeHtml(node.label) + '</span>';
                    html += '</div>';

                    if (hasChildren) {
                        buildTree(node.children, depth + 1);
                    }
                });
            }

            buildTree(treeData);
            this.els.tree.html(html);

            this.els.tree.on('click', '.phantom-ds-tree-item', function () {
                var component = $(this).data('component');
                var instance = $(this).data('instance');
                if (component) {
                    self.selectComponent(component, instance);
                }
                $(this).parent().find('.phantom-ds-tree-item.selected').removeClass('selected');
                $(this).addClass('selected');
            });

            this.els.tree.on('click', '.phantom-ds-tree-toggle.dashicons-arrow-down', function () {
                $(this).removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
                $(this).closest('.phantom-ds-tree-item').nextUntil('.is-page').hide();
            });
            this.els.tree.on('click', '.phantom-ds-tree-toggle.dashicons-arrow-right', function () {
                $(this).removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
                $(this).closest('.phantom-ds-tree-item').nextUntil('.is-page').show();
            });
        },

        filterTree: function () {
            var query = this.els.search.val().toLowerCase();
            this.els.tree.find('.phantom-ds-tree-item').each(function () {
                var label = $(this).find('.phantom-ds-tree-label').text().toLowerCase();
                $(this).toggle(label.indexOf(query) !== -1);
            });
        },

        selectComponent: function (component, instance) {
            this.state.selectedComponent = component;
            this.state.selectedInstance = instance || null;
            this.renderInspector(component, instance);
            this.updateBreadcrumb(component, instance, this.state.selectedBreadcrumb);
        },

        renderInspector: function (component, instance) {
            var self = this;
            var body = this.els.inspectorBody;

            body.html('<div class="phantom-ds-inspector-loading"><span class="spinner" style="display:inline-block;float:none;visibility:visible;"></span></div>');

            $.ajax({
                url: phantomDS.restUrl + '/design-studio/component/' + encodeURIComponent(component),
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                success: function (resp) {
                    if (resp.success && resp.definition) {
                        self.buildInspectorTabs(body, resp.definition, instance);
                    } else {
                        body.html('<div class="phantom-ds-no-selection"><p>' + escapeHtml(phantomDS.strings.noSelection) + '</p></div>');
                    }
                },
                error: function () {
                    body.html('<div class="phantom-ds-no-selection"><p>' + escapeHtml(phantomDS.strings.noSelection) + '</p></div>');
                },
            });
        },

        buildInspectorTabs: function (container, definition, instance) {
            var self = this;
            var tabs = definition.tabs || [];
            var html = '';

            html += '<div class="phantom-ds-inspector-tabs">';
            $.each(tabs, function (i, tab) {
                var active = i === 0 ? ' active' : '';
                html += '<button type="button" class="phantom-ds-tab-btn' + active + '" data-tab="' + escapeAttr(tab.key) + '">' + escapeHtml(tab.label) + '</button>';
            });
            html += '</div>';

            html += '<div class="phantom-ds-inspector-panels">';
            $.each(tabs, function (i, tab) {
                var active = i === 0 ? ' active' : '';
                html += '<div class="phantom-ds-tab-panel' + active + '" data-tab="' + escapeAttr(tab.key) + '">';
                html += self.buildFieldsHtml(tab.fields || [], instance);
                html += '</div>';
            });
            html += '</div>';

            container.html(html);

            container.on('click', '.phantom-ds-tab-btn', function () {
                var tabKey = $(this).data('tab');
                container.find('.phantom-ds-tab-btn').removeClass('active');
                $(this).addClass('active');
                container.find('.phantom-ds-tab-panel').removeClass('active');
                container.find('.phantom-ds-tab-panel[data-tab="' + tabKey + '"]').addClass('active');
            });

            container.on('change', '.phantom-ds-field-input', function () {
                var key = $(this).closest('.phantom-ds-field').data('key');
                var val = $(this).val();
                self.onFieldChange(key, val, $(this));
            });

            container.on('click', '.phantom-ds-field-reset', function () {
                var field = $(this).closest('.phantom-ds-field');
                var key = field.data('key');
                var defaultValue = field.data('default') || '';
                field.find('.phantom-ds-field-input').val(defaultValue);
                self.onFieldChange(key, defaultValue, field.find('.phantom-ds-field-input'));
            });

            container.on('click', '.phantom-ds-image-upload', function () {
                var field = $(this).closest('.phantom-ds-field');
                var key = field.data('key');
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({
                        title: phantomDS.strings.upload || 'Select Image',
                        button: { text: phantomDS.strings.upload || 'Use Image' },
                        multiple: false
                    });
                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        var url = attachment.url || '';
                        field.find('.phantom-ds-image-preview').attr('src', url);
                        field.find('.phantom-ds-field-input').val(url);
                        self.onFieldChange(key, url, field.find('.phantom-ds-field-input'));
                    });
                    frame.open();
                }
            });

            container.on('click', '.phantom-ds-image-reset', function () {
                var field = $(this).closest('.phantom-ds-field');
                var key = field.data('key');
                field.find('.phantom-ds-image-preview').attr('src', '');
                field.find('.phantom-ds-field-input').val('');
                self.onFieldChange(key, '', field.find('.phantom-ds-field-input'));
            });
        },

        buildFieldsHtml: function (fields, instance) {
            var html = '';
            $.each(fields, function (i, field) {
                var value = field.value || field.default || '';
                html += '<div class="phantom-ds-field" data-key="' + escapeAttr(field.key) + '" data-type="' + escapeAttr(field.type) + '" data-default="' + escapeAttr(field.default || '') + '">';
                html += '<label class="phantom-ds-field-label">' + escapeHtml(field.label) + '</label>';
                html += '<div class="phantom-ds-field-control">';
                html += self.buildFieldInput(field, value);
                html += '<button type="button" class="phantom-ds-field-reset dashicons dashicons-image-rotate" title="' + escapeAttr(phantomDS.strings.reset) + '"></button>';
                html += '</div>';
                html += '</div>';
            });
            return html;
        },

        buildFieldInput: function (field, value) {
            switch (field.type) {
                case 'color':
                    return '<input type="color" class="phantom-ds-field-input phantom-ds-field-color" value="' + escapeAttr(value) + '" />';
                case 'select':
                    var opts = field.options || [];
                    var s = '<select class="phantom-ds-field-input phantom-ds-field-select">';
                    $.each(opts, function (i, opt) {
                        var selected = opt === value ? ' selected' : '';
                        s += '<option value="' + escapeAttr(opt) + '"' + selected + '>' + escapeHtml(opt) + '</option>';
                    });
                    s += '</select>';
                    return s;
                case 'toggle':
                    var checked = value ? ' checked' : '';
                    return '<label class="phantom-ds-field-toggle"><input type="checkbox" class="phantom-ds-field-input"' + checked + ' /><span class="phantom-ds-toggle-track"></span></label>';
                case 'slider':
                    var min = field.min || 0;
                    var max = field.max || 100;
                    var step = field.step || 1;
                    var unit = field.unit || '';
                    return '<div class="phantom-ds-slider-group"><input type="range" class="phantom-ds-field-input phantom-ds-field-slider" value="' + escapeAttr(value) + '" min="' + min + '" max="' + max + '" step="' + step + '" /><input type="number" class="phantom-ds-slider-value" value="' + escapeAttr(value) + '" min="' + min + '" max="' + max + '" step="' + step + '" /><span class="phantom-ds-slider-unit">' + escapeHtml(unit) + '</span></div>';
                case 'textarea':
                    return '<textarea class="phantom-ds-field-input phantom-ds-field-textarea" rows="3">' + escapeHtml(value) + '</textarea>';
                case 'code':
                    return '<textarea class="phantom-ds-field-input phantom-ds-field-code" rows="5" spellcheck="false">' + escapeHtml(value) + '</textarea>';
                case 'image':
                    var preview = value ? '<img src="' + escapeAttr(value) + '" class="phantom-ds-image-preview" />' : '';
                    return '<div class="phantom-ds-image-field">' + preview + '<div class="phantom-ds-image-actions"><button type="button" class="button phantom-ds-image-upload">' + escapeHtml(phantomDS.strings.upload || 'Upload') + '</button><button type="button" class="button phantom-ds-image-reset">' + escapeHtml(phantomDS.strings.reset || 'Reset') + '</button></div></div>';
                default:
                    return '<input type="text" class="phantom-ds-field-input phantom-ds-field-text" value="' + escapeAttr(value) + '" />';
            }
        },

        onFieldChange: function (key, value, $el) {
            this.setDirty(true);

            // Debounce: store pending change and clear previous timer
            if (!this._pendingChanges) this._pendingChanges = {};
            if (!this._pendingTimer) this._pendingTimer = null;

            var self = this;
            this._pendingChanges[key] = value;

            if (this._pendingTimer) {
                clearTimeout(this._pendingTimer);
            }

            this._pendingTimer = setTimeout(function () {
                var data = self._pendingChanges;
                self._pendingChanges = {};
                self._pendingTimer = null;

                $.ajax({
                    url: phantomDS.restUrl + '/design-studio/preview',
                    method: 'POST',
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                    },
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    success: function (resp) {
                        if (resp.success && resp.cssVars) {
                            self.sendMessage({
                                type: 'APPLY_CSS_VARS',
                                cssVars: resp.cssVars,
                            });
                            self.els.statusSave.text(phantomDS.strings.unsaved);
                        }
                    },
                    error: function (jqXHR, textStatus) {
                        if (window.console && console.warn) {
                            console.warn('[Design Studio] Preview update failed:', textStatus);
                        }
                    },
                });
            }, 150);
        },

        updateBreadcrumb: function (component, instance, chain) {
            if (chain && chain.length > 0) {
                var html = '';
                $.each(chain, function (i, item) {
                    var label = item.component.charAt(0).toUpperCase() + item.component.slice(1);
                    if (item.instance) {
                        label += ' \u2014 ' + item.instance;
                    }
                    if (i < chain.length - 1) {
                        html += '<span class="phantom-ds-bc-item">' + escapeHtml(label) + '</span>';
                        html += '<span class="phantom-ds-bc-sep">></span>';
                    } else {
                        html += '<span class="phantom-ds-bc-item active">' + escapeHtml(label) + '</span>';
                    }
                });
                this.els.breadcrumb.html(html);
                return;
            }
            if (!component) {
                this.els.breadcrumb.html('<span class="phantom-ds-bc-item">' + phantomDS.strings.selectComponent + '</span>');
                return;
            }
            var label = component.charAt(0).toUpperCase() + component.slice(1);
            if (instance) {
                label += ' \u2014 ' + instance;
            }
            this.els.breadcrumb.html(
                '<span class="phantom-ds-bc-item">Page</span>' +
                '<span class="phantom-ds-bc-sep">/</span>' +
                '<span class="phantom-ds-bc-item active">' + escapeHtml(label) + '</span>'
            );
        },

        updateBreadcrumbFromChain: function (chain) {
            if (!chain || chain.length === 0) {
                this.els.breadcrumb.html('');
                return;
            }
            var html = '';
            $.each(chain, function (i, item) {
                var label = item.component.charAt(0).toUpperCase() + item.component.slice(1);
                if (item.instance) {
                    label += ' \u2014 ' + item.instance;
                }
                if (i < chain.length - 1) {
                    html += '<span class="phantom-ds-bc-item">' + escapeHtml(label) + '</span>';
                    html += '<span class="phantom-ds-bc-sep">></span>';
                } else {
                    html += '<span class="phantom-ds-bc-item phantom-ds-bc-hover">' + escapeHtml(label) + '</span>';
                }
            });
            this.els.breadcrumb.html(html);
        },

        setDirty: function (dirty) {
            this.state.isDirty = dirty;
            this.els.statusSave.text(dirty ? phantomDS.strings.unsaved : phantomDS.strings.saved);
            this.els.publishBtn.prop('disabled', !dirty);
            this.els.saveDraftBtn.prop('disabled', !dirty);
            this.els.undoBtn.prop('disabled', !dirty);
        },

        setupIframeBridge: function () {
            var self = this;
            var iframe = this.els.iframe[0];

            function sendInit() {
                try {
                    var iframeWin = iframe.contentWindow || iframe.contentDocument;
                    iframeWin.postMessage({ type: 'DESIGN_STUDIO_INIT', restUrl: phantomDS.restUrl, nonce: phantomDS.nonce }, '*');
                } catch (e) {}
            }

            this.els.iframe.on('load', sendInit);

            try {
                if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
                    sendInit();
                }
            } catch (e) {}

            $(window).on('message', function (e) {
                var msg = e.originalEvent.data;
                if (!msg || !msg.type) return;

                switch (msg.type) {
                    case 'IFRAME_READY':
                        self.sendMessage({ type: 'INIT_ACK', darkMode: self.state.darkMode, device: self.state.device });
                        break;
                    case 'COMPONENT_CLICK':
                        self.state.selectedBreadcrumb = msg.breadcrumb || null;
                        self.selectComponent(msg.component, msg.instance);
                        break;
                    case 'COMPONENT_HOVER':
                        self.updateBreadcrumbFromChain(msg.breadcrumb || []);
                        break;
                    case 'HOVER_END':
                        self.updateBreadcrumb(self.state.selectedComponent, self.state.selectedInstance, self.state.selectedBreadcrumb);
                        break;
                }
            });
        },

        sendMessage: function (data) {
            try {
                var iframe = this.els.iframe[0];
                var iframeWin = iframe.contentWindow || iframe.contentDocument;
                if (iframeWin) {
                    iframeWin.postMessage(data, '*');
                }
            } catch (e) {
            }
        },

        saveDraft: function () {
            var self = this;

            this.sendMessage({ type: 'SAVE_DRAFT' });

            // Commit current preview values as a draft via publish
            $.ajax({
                url: phantomDS.restUrl + '/design-studio/publish',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                data: { snapshot: true },
                success: function (resp) {
                    if (resp.success) {
                        self.setDirty(false);
                        self.showToast('Draft saved', 'success');
                        // Reload iframe to show saved state
                        self.els.iframe.attr('src', self.els.iframe.attr('src'));
                    } else {
                        self.showToast('Failed to save draft', 'error');
                    }
                },
                error: function () {
                    self.showToast('Network error saving draft', 'error');
                },
            });
        },

        publish: function () {
            if (this.state.isPublishing) return;
            var self = this;
            this.state.isPublishing = true;

            this.els.overlay.show();
            this.els.overlayMsg.text(phantomDS.strings.publishing);
            this.els.publishBtn.prop('disabled', true);

            $.ajax({
                url: phantomDS.restUrl + '/design-studio/publish',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                data: { snapshot: true },
                success: function (resp) {
                    if (resp.success) {
                        var msg = phantomDS.strings.published;
                        if (resp.snapshot_id) {
                            msg += ' (#' + resp.snapshot_id.substring(0, 8) + ')';
                        }
                        self.showToast(msg, 'success');
                        self.setDirty(false);
                        self.els.statusHistory.text('Step 0/0');
                        self.sendMessage({ type: 'PUBLISHED' });
                        // Reload iframe to show published state
                        setTimeout(function () {
                            self.els.iframe.attr('src', self.els.iframe.attr('src'));
                        }, 500);
                    } else {
                        self.showToast(phantomDS.strings.publishFailed, 'error');
                    }
                },
                error: function () {
                    self.showToast(phantomDS.strings.publishFailed + ': network error', 'error');
                },
                complete: function () {
                    self.els.overlay.hide();
                    self.state.isPublishing = false;
                    self.els.publishBtn.prop('disabled', self.state.isDirty ? false : true);
                },
            });
        },

        undo: function () {
            var self = this;
            $.ajax({
                url: phantomDS.restUrl + '/design-studio/undo',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                success: function (resp) {
                    if (resp.success) {
                        self.sendMessage({ type: 'STATE_RESTORED', state: resp.state });
                        self.showToast('Undo', 'info');
                    }
                },
            });
        },

        redo: function () {
            var self = this;
            $.ajax({
                url: phantomDS.restUrl + '/design-studio/redo',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                success: function (resp) {
                    if (resp.success) {
                        self.sendMessage({ type: 'STATE_RESTORED', state: resp.state });
                        self.showToast('Redo', 'info');
                    }
                },
            });
        },

        openHistory: function () {
            this.showToast('History panel coming soon', 'info');
        },

        exportDesign: function () {
            var self = this;
            // Use AJAX with proper nonce header, then trigger download
            $.ajax({
                url: phantomDS.restUrl + '/design-studio/export',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', phantomDS.nonce);
                },
                success: function (resp) {
                    if (resp.success && resp.export) {
                        var blob = new Blob(
                            [JSON.stringify(resp.export, null, 2)],
                            { type: 'application/json' }
                        );
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'phantom-design-export-' + new Date().toISOString().slice(0, 10) + '.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        self.showToast('Export downloaded', 'success');
                    } else {
                        self.showToast('Export failed: no data', 'error');
                    }
                },
                error: function () {
                    self.showToast('Export failed: network error', 'error');
                },
            });
        },

        showToast: function (message, type) {
            type = type || 'info';
            if (!$('#phantom-ds-toast-container').length) {
                $('<div id="phantom-ds-toast-container">').css({
                    position: 'fixed',
                    bottom: '48px',
                    right: '20px',
                    'z-index': 100000,
                    display: 'flex',
                    'flex-direction': 'column',
                    gap: '8px',
                }).appendTo('body');
            }
            var className = 'phantom-ds-toast phantom-toast-' + type;
            var toast = $('<div class="' + className + '">').text(message);
            $('#phantom-ds-toast-container').append(toast);
            setTimeout(function () {
                toast.addClass('phantom-toast-out');
                setTimeout(function () { toast.remove(); }, 300);
            }, 3000);
        },
    };

    function escapeHtml(str) {
        if (!str) return '';
        return $('<span>').text(str).html();
    }

    function escapeAttr(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    $(document).ready(function () {
        DS.init();
    });

})(jQuery);