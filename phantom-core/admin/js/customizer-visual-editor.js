(function () {
    'use strict';

    if (window.PhantomCustomizerVisual) return;
    window.PhantomCustomizerVisual = true;

    var api = window.wp && wp.customize;
    if (!api) return;

    var cfg = window.PhantomVisualEditor || {};
    var editingEnabled = false;
    var currentComponent = null;
    var currentTool = '';
    var toolsCache = {};
    var pendingChanges = {};
    var previewFrame = null;

    function getPreviewFrame() {
        if (previewFrame && previewFrame.contentWindow) return previewFrame;
        previewFrame = document.querySelector('iframe[name^="customize-preview"]') ||
            document.getElementById('customize-preview-iframe') ||
            document.querySelector('#customize-preview iframe');
        return previewFrame;
    }

    function restRoute(path, qs) {
        qs = qs || '';
        var base = cfg.restUrl || '';
        if (base.indexOf('rest_route=') !== -1) {
            var m = base.match(/([?&])rest_route=([^&]*)/);
            if (m) {
                var route = decodeURIComponent(m[2]) + path;
                var url = base.slice(0, m.index) + m[1] + 'rest_route=' + encodeURIComponent(route);
                return url + (qs ? '&' + qs : '');
            }
        }
        var url = base.replace(/\/+$/, '');
        return url + path + (qs ? (url.indexOf('?') !== -1 ? '&' : '?') + qs : '');
    }

    function restNonce() {
        return (window.wpApiSettings && window.wpApiSettings.nonce) || cfg.wpRestNonce || cfg.nonce;
    }

    function postToPreview(msg) {
        var frame = getPreviewFrame();
        if (frame && frame.contentWindow) {
            frame.contentWindow.postMessage(msg, '*');
        }
    }

    function inspectorEl() {
        return document.getElementById('phantom-visual-inspector');
    }

    function expandSection(id) {
        var section = api.section(id);
        if (section && section.expand) {
            try { section.expand(); } catch (e) {}
        }
    }

    function cssVarFor(prop) {
        if (cfg.varMap && cfg.varMap[prop]) return cfg.varMap[prop];
        return '--' + prop.replace(/_/g, '-');
    }

    function applyLive(prop, value) {
        pendingChanges[prop] = value;
        var vars = {};
        vars[cssVarFor(prop)] = value;
        postToPreview({ type: 'vc-apply-css', cssVars: vars });

        var settingId = 'phantom_' + prop;
        var setting = api(settingId);
        if (setting && setting.set) {
            try { setting.set(value); } catch (e) {}
        }
    }

    function loadInspector(component, state, viewport, tool, done) {
        state = state || (component && component.state) || 'normal';
        viewport = viewport || (component && component.viewport) || 'desktop';
        tool = (tool !== undefined) ? tool : currentTool;

        var inspector = inspectorEl();
        if (!inspector) return;

        inspector.innerHTML = '<div class="vc-panel vc-loading"><span class="spinner is-active"></span></div>';

        var qs = 'state=' + encodeURIComponent(state) + '&viewport=' + encodeURIComponent(viewport);
        if (tool) {
            qs += '&tool=' + encodeURIComponent(tool);
        }
        if (component && component.editable && component.editable.length) {
            qs += '&editable=' + encodeURIComponent(JSON.stringify(component.editable));
        }
        var url = restRoute('/components/' + encodeURIComponent(component.component) + '/inspector', qs);

        fetch(url, { headers: { 'X-WP-Nonce': restNonce() } })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                var html = (json && json.data && json.data.panels) ? json.data.panels : '';
                if (!html) {
                    html = '<div class="vc-panel vc-panel-error">No inspector available for this element.</div>';
                }
                inspector.innerHTML = html;
                if (json && json.data && json.data.tools) {
                    toolsCache = {};
                    (json.data.tools || []).forEach(function (t) { toolsCache[t.tool] = t; });
                    renderToolPalette(inspector, json.data.tools, currentTool);
                }
                bindControls(inspector);
                expandSection('phantom_section_inspector');
                if (done) done();
            })
            .catch(function () {
                inspector.innerHTML = '<div class="vc-panel vc-panel-error">Failed to load the inspector.</div>';
            });
    }

    function renderToolPalette(container, tools, activeTool) {
        if (!tools || !tools.length) return;

        var el = document.createElement('div');
        el.className = 'vc-tool-palette';

        var allActive = (activeTool === '') ? ' active' : '';
        el.innerHTML = '<button type="button" class="vc-tool-btn' + allActive + '" data-tool="" title="Show all settings">' +
            '<span class="dashicons dashicons-admin-generic"></span><span>All</span></button>';

        tools.forEach(function (t) {
            var active = (t.tool === activeTool) ? ' active' : '';
            var soon = t.implemented ? '' : ' vc-tool-soon';
            var title = t.implemented ? t.label : t.label + ' (coming soon)';
            el.innerHTML += '<button type="button" class="vc-tool-btn' + active + soon + '" data-tool="' + t.tool + '"' +
                ' title="' + title + '">' +
                '<span class="dashicons dashicons-' + t.icon + '"></span>' +
                '<span>' + t.label + '</span>' +
                (t.implemented ? '' : '<span class="vc-tool-soon-badge">Soon</span>') +
                '</button>';
        });

        container.insertBefore(el, container.firstChild);

        el.addEventListener('click', function (ev) {
            var btn = ev.target && ev.target.closest ? ev.target.closest('.vc-tool-btn') : null;
            if (!btn || btn.classList.contains('active')) return;
            var tool = btn.getAttribute('data-tool');
            if (btn.classList.contains('vc-tool-soon')) {
                showToolSoonNotice(tool);
                return;
            }
            selectTool(tool);
        });
    }

    function showToolSoonNotice(tool) {
        var def = toolsCache[tool] || {};
        var label = def.label || tool;
        var notice = document.querySelector('.vc-tool-soon-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'vc-tool-soon-notice';
            var inspector = document.getElementById('phantom-visual-inspector');
            if (!inspector) return;
            inspector.insertBefore(notice, inspector.querySelector('.vc-panel') || inspector.firstChild);
        }
        notice.innerHTML = '<span class="dashicons dashicons-hammer"></span>' +
            '<span>The <strong>' + label + '</strong> tool is coming soon.</span>' +
            '<button type="button" class="vc-tool-soon-dismiss" title="Dismiss"><span class="dashicons dashicons-no-alt"></span></button>';
        notice.style.display = '';
        var dismiss = notice.querySelector('.vc-tool-soon-dismiss');
        if (dismiss && !dismiss.dataset.vcBound) {
            dismiss.dataset.vcBound = '1';
            dismiss.addEventListener('click', function () {
                notice.style.display = 'none';
            });
        }
    }

    function selectTool(tool) {
        currentTool = tool || '';
        if (currentComponent) {
            loadInspector(currentComponent, undefined, undefined, currentTool);
        }
    }

    function bindControls(container) {
        container.querySelectorAll('.vc-color-picker').forEach(function (input) {
            if (input.dataset.vcBound) return;
            input.dataset.vcBound = '1';
            var prop = input.getAttribute('data-property');
            var swatch = input.parentNode ? input.parentNode.querySelector('.vc-color-swatch') : null;

            if (window.jQuery && jQuery.fn.wpColorPicker) {
                try {
                    jQuery(input).wpColorPicker({
                        change: function (event, ui) {
                            var val = ui.color ? ui.color.toString() : input.value;
                            input.value = val;
                            if (swatch) swatch.style.background = val;
                            applyLive(prop, val);
                        },
                        clear: function () {
                            applyLive(prop, '');
                            if (swatch) swatch.style.background = '';
                        }
                    });
                    return;
                } catch (e) {}
            }
            input.addEventListener('change', function () {
                if (swatch) swatch.style.background = input.value;
                applyLive(prop, input.value);
            });
        });

        container.querySelectorAll('.vc-range').forEach(function (range) {
            range.addEventListener('input', function () {
                var num = range.parentNode ? range.parentNode.querySelector('.vc-range-value') : null;
                if (num) num.value = range.value;
                applyLive(range.getAttribute('data-property'), range.value);
            });
        });

        container.querySelectorAll('.vc-range-value').forEach(function (num) {
            num.addEventListener('change', function () {
                var range = num.parentNode ? num.parentNode.querySelector('.vc-range') : null;
                if (range) range.value = num.value;
                applyLive(num.getAttribute('data-property'), num.value);
            });
        });

        container.querySelectorAll('.vc-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                applyLive(sel.getAttribute('data-property'), sel.value);
            });
        });

        container.querySelectorAll('.vc-text-input').forEach(function (input) {
            input.addEventListener('change', function () {
                applyLive(input.getAttribute('data-property'), input.value);
            });
        });

        container.querySelectorAll('.vc-panel-header[data-panel]').forEach(function (header) {
            header.addEventListener('click', function () {
                var body = header.parentNode ? header.parentNode.querySelector('.vc-panel-body') : null;
                var toggle = header.querySelector('.vc-panel-toggle');
                if (body) {
                    var hidden = (body.style.display === 'none');
                    body.style.display = hidden ? '' : 'none';
                    if (toggle) {
                        toggle.classList.toggle('dashicons-arrow-down', !hidden);
                        toggle.classList.toggle('dashicons-arrow-up', hidden);
                    }
                }
            });
        });

        container.querySelectorAll('.vc-state-btn[data-state]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                container.querySelectorAll('.vc-state-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                loadInspector(currentComponent, btn.getAttribute('data-state'));
            });
        });

        container.querySelectorAll('.vc-viewport-option[data-viewport]').forEach(function (opt) {
            opt.addEventListener('click', function () {
                container.querySelectorAll('.vc-viewport-option').forEach(function (b) { b.classList.remove('active'); });
                opt.classList.add('active');
                loadInspector(currentComponent, undefined, opt.getAttribute('data-viewport'));
            });
        });

        container.querySelectorAll('.vc-btn-upload').forEach(function (btn) {
            btn.addEventListener('click', function () {
                uploadAsset(btn.getAttribute('data-asset'), btn);
            });
        });

        container.querySelectorAll('.vc-btn-reset').forEach(function (btn) {
            btn.addEventListener('click', function () {
                resetAsset(btn.getAttribute('data-asset'), btn);
            });
        });
    }

    function uploadAsset(key, btn) {
        if (!window.wp || !wp.media) return;
        var frame = wp.media({
            title: 'Upload ' + key,
            button: { text: 'Use image' },
            multiple: false
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            if (!attachment || !attachment.id) return;
            if (btn) btn.disabled = true;
            var body = new URLSearchParams();
            body.append('key', key);
            body.append('id', String(attachment.id));
            fetch(restRoute('/assets/set'), {
                method: 'POST',
                headers: { 'X-WP-Nonce': restNonce(), 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (json && json.success && json.url) {
                        updateAssetRow(key, json.url);
                        postToPreview({ type: 'vc-asset-changed', key: key, url: json.url });
                    }
                    if (btn) btn.disabled = false;
                })
                .catch(function () {
                    if (btn) btn.disabled = false;
                });
        });
        frame.open();
    }

    function resetAsset(key, btn) {
        if (btn) btn.disabled = true;
        var body = new URLSearchParams();
        body.append('key', key);
        fetch(restRoute('/assets/reset'), {
            method: 'POST',
            headers: { 'X-WP-Nonce': restNonce(), 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.success) {
                    updateAssetRow(key, json.url || '');
                    postToPreview({ type: 'vc-asset-changed', key: key, url: json.url || '' });
                }
                if (btn) btn.disabled = false;
            })
            .catch(function () {
                if (btn) btn.disabled = false;
            });
    }

    function updateAssetRow(key, url) {
        var row = document.querySelector('.vc-asset-row[data-asset="' + key + '"]');
        if (!row) return;
        var img = row.querySelector('.vc-asset-preview');
        if (img && url) {
            img.src = url;
        }
        var reset = row.querySelector('.vc-btn-reset');
        if (reset) {
            reset.disabled = !url;
        }
    }

    function flushPending() {
        var keys = Object.keys(pendingChanges);
        if (!keys.length) return;
        var toSend = {};
        keys.forEach(function (k) {
            if (!api('phantom_' + k)) {
                toSend[k] = pendingChanges[k];
            }
        });
        if (!Object.keys(toSend).length) {
            pendingChanges = {};
            return;
        }
        var body = new URLSearchParams();
        Object.keys(toSend).forEach(function (k) {
            body.append('settings[' + k + ']', toSend[k]);
        });
        fetch(restRoute('/settings'), {
            method: 'POST',
            headers: { 'X-WP-Nonce': restNonce(), 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function () { pendingChanges = {}; })
            .catch(function () {});
    }

    function renderSelectionCrumb(data) {
        var inspector = inspectorEl();
        if (!inspector) return;

        var crumb = inspector.querySelector('.vc-selection-crumb');
        if (!data || !data.breadcrumb) {
            if (crumb) crumb.remove();
            return;
        }

        if (!crumb) {
            crumb = document.createElement('div');
            crumb.className = 'vc-selection-crumb';
            crumb.title = 'Selected element path';
            inspector.insertBefore(crumb, inspector.firstChild);
        }
        crumb.textContent = data.breadcrumb;

        var part = data.part || '';
        inspector.querySelectorAll('.vc-panel-header').forEach(function (h) {
            if (part && h.getAttribute('data-panel') === part) {
                h.classList.add('vc-part-active');
            } else {
                h.classList.remove('vc-part-active');
            }
        });
    }

    window.addEventListener('message', function (e) {
        if (!e.data || !e.data.type) return;
        switch (e.data.type) {
            case 'vc-element-selected':
                currentComponent = e.data.data || {};
                loadInspector(currentComponent, undefined, undefined, currentTool, function () {
                    renderSelectionCrumb(currentComponent);
                });
                break;
            case 'vc-element-locked':
                break;
            case 'vc-engine-ready':
                break;
        }
    });

    function bindToggle() {
        var toggle = document.getElementById('phantom-live-preview-edit');
        if (!toggle || toggle.__vcBound) return;
        toggle.__vcBound = true;
        toggle.addEventListener('change', function () {
            editingEnabled = toggle.checked;
            postToPreview({ type: 'vc-editing-mode', enabled: editingEnabled });
            if (editingEnabled) {
                expandSection('phantom_section_live_preview');
            }
        });
    }

    bindToggle();
    api.bind('ready', bindToggle);

    api.bind('saved', function () {
        flushPending();
    });

    window.PhantomVisualEditorApi = {
        applyLive: applyLive,
        loadInspector: loadInspector,
        selectTool: selectTool,
        getSelected: function () { return currentComponent; },
        getTool: function () { return currentTool; }
    };
})();
