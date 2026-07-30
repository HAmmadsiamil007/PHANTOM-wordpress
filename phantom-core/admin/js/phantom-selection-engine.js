(function () {
    'use strict';

    if (window._phantomSelectionEngineInited) return;
    window._phantomSelectionEngineInited = true;

    var meta = document.querySelector('meta[name="phantom-design-studio"]');
    if (!meta || meta.getAttribute('content') !== '1') return;

    if (window === window.parent) return;

    var state = {
        selected: null,
        hovered: null,
        enabled: true,
    };

    var readySent = false;

    function sendParent(data) {
        try {
            window.parent.postMessage(data, '*');
        } catch (e) {}
    }

    function sendReady() {
        if (!readySent) {
            readySent = true;
            sendParent({ type: 'IFRAME_READY' });
        }
    }

    var overlay = document.createElement('div');
    overlay.id = 'phantom-se-overlay';
    overlay.style.cssText = [
        'position:fixed',
        'pointer-events:none',
        'z-index:99999',
        'background:rgba(59,130,246,0.12)',
        'border:2px solid #3b82f6',
        'border-radius:3px',
        'transition:all 0.12s ease-out',
        'display:none',
    ].join(';');
    document.body.appendChild(overlay);

    var clickOverlay = document.createElement('div');
    clickOverlay.id = 'phantom-se-click-overlay';
    clickOverlay.style.cssText = [
        'position:fixed',
        'pointer-events:none',
        'z-index:99998',
        'background:rgba(59,130,246,0.08)',
        'border:2px solid #2563eb',
        'border-radius:3px',
        'box-shadow:0 0 0 1px rgba(37,99,235,0.3)',
        'display:none',
    ].join(';');
    document.body.appendChild(clickOverlay);

    function getComponentElement(el) {
        while (el && el !== document.body && el !== document.documentElement) {
            if (el.hasAttribute && el.hasAttribute('data-component')) {
                var editable = el.getAttribute('data-editable');
                if (editable === 'false') return null;
                return el;
            }
            el = el.parentElement;
        }
        return null;
    }

    function getComponentData(el) {
        var component = el.getAttribute('data-component') || '';
        var instance = el.getAttribute('data-instance') || '';
        var settingsGroup = el.getAttribute('data-settings-group') || component;
        var tokenGroup = el.getAttribute('data-token-group') || '';
        return {
            component: component,
            instance: instance,
            settingsGroup: settingsGroup,
            tokenGroup: tokenGroup,
        };
    }

    function getBreadcrumbChain(el) {
        var chain = [];
        var current = el;
        while (current && current !== document.body && current !== document.documentElement) {
            if (current.hasAttribute && current.hasAttribute('data-component')) {
                var editable = current.getAttribute('data-editable');
                if (editable !== 'false') {
                    chain.unshift({
                        component: current.getAttribute('data-component'),
                        instance: current.getAttribute('data-instance') || '',
                    });
                }
            }
            current = current.parentElement;
        }
        return chain;
    }

    function positionOverlay(target, overlayEl) {
        var rect = target.getBoundingClientRect();
        overlayEl.style.top = rect.top + 'px';
        overlayEl.style.left = rect.left + 'px';
        overlayEl.style.width = rect.width + 'px';
        overlayEl.style.height = rect.height + 'px';
        overlayEl.style.display = 'block';
    }

    function isFormInteractive(el) {
        if (!el || !el.tagName) return false;
        var tag = el.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button' || el.isContentEditable;
    }

    function onMouseOver(e) {
        if (!state.enabled) return;
        var target = e.target;
        if (!target) return;

        var compEl = getComponentElement(target);
        if (!compEl) {
            if (state.hovered) {
                overlay.style.display = 'none';
                state.hovered = null;
                sendParent({ type: 'HOVER_END' });
            }
            return;
        }

        if (compEl === state.hovered) return;

        state.hovered = compEl;
        var data = getComponentData(compEl);
        positionOverlay(compEl, overlay);

        var breadcrumb = getBreadcrumbChain(compEl);
        sendParent({
            type: 'COMPONENT_HOVER',
            component: data.component,
            instance: data.instance,
            breadcrumb: breadcrumb,
        });
    }

    function onMouseOut(e) {
        if (!state.enabled) return;
        var target = e.target;
        if (!target) return;

        var compEl = getComponentElement(target);

        if (state.hovered && state.hovered === compEl) {
            var related = e.relatedTarget;
            if (related && compEl.contains(related)) return;
            overlay.style.display = 'none';
            state.hovered = null;
            sendParent({ type: 'HOVER_END' });
        }
    }

    function onClick(e) {
        if (!state.enabled) return;
        var target = e.target;
        if (!target) return;

        var selectedEl = getComponentElement(target);
        if (!selectedEl) return;

        if (isFormInteractive(target)) return;

        e.preventDefault();
        e.stopPropagation();

        state.selected = selectedEl;
        var data = getComponentData(selectedEl);
        positionOverlay(selectedEl, clickOverlay);
        overlay.style.display = 'none';

        var breadcrumb = getBreadcrumbChain(selectedEl);
        sendParent({
            type: 'COMPONENT_CLICK',
            component: data.component,
            instance: data.instance,
            settingsGroup: data.settingsGroup,
            tokenGroup: data.tokenGroup,
            breadcrumb: breadcrumb,
        });
    }

    function selectComponent(component, instance) {
        var selector = '[data-component="' + component + '"]';
        if (instance) {
            selector += '[data-instance="' + instance + '"]';
        }
        var el = document.querySelector(selector);
        if (el && el.getAttribute('data-editable') !== 'false') {
            state.selected = el;
            positionOverlay(el, clickOverlay);
            overlay.style.display = 'none';
        }
    }

    function clearSelection() {
        state.selected = null;
        clickOverlay.style.display = 'none';
        overlay.style.display = 'none';
    }

    function rebuildOverlayPositions() {
        if (state.hovered && state.hovered !== state.selected) {
            var rect = state.hovered.getBoundingClientRect();
            overlay.style.top = rect.top + 'px';
            overlay.style.left = rect.left + 'px';
            overlay.style.width = rect.width + 'px';
            overlay.style.height = rect.height + 'px';
        }
        if (state.selected) {
            var rect = state.selected.getBoundingClientRect();
            clickOverlay.style.top = rect.top + 'px';
            clickOverlay.style.left = rect.left + 'px';
            clickOverlay.style.width = rect.width + 'px';
            clickOverlay.style.height = rect.height + 'px';
        }
    }

    // ── Live Preview CSS Injection ──────────────────────────────

    function injectCSSVars(cssVars) {
        if (!cssVars || typeof cssVars !== 'object') return;

        var styleId = 'phantom-ds-preview-css';
        var existing = document.getElementById(styleId);
        if (!existing) {
            existing = document.createElement('style');
            existing.id = styleId;
            document.head.appendChild(existing);
        }

        var rootVars = [];
        for (var key in cssVars) {
            if (cssVars.hasOwnProperty(key)) {
                rootVars.push('  ' + key + ': ' + cssVars[key] + ';');
            }
        }

        existing.textContent = ':root {\n' + rootVars.join('\n') + '\n}';
    }

    function removePreviewCSS() {
        var el = document.getElementById('phantom-ds-preview-css');
        if (el) el.parentNode.removeChild(el);
    }

    // ── Single merged message handler ─────────────────────────────────

    function onParentMessage(e) {
        var msg = e.data;
        if (!msg || !msg.type) return;

        switch (msg.type) {
            // Selection Engine
            case 'DESIGN_STUDIO_INIT':
                state.enabled = true;
                sendReady();
                return;

            case 'DEVICE_CHANGE':
                document.body.setAttribute('data-ds-device', msg.device || 'desktop');
                clearSelection();
                return;

            case 'DARK_MODE_TOGGLE':
                document.body.setAttribute('data-ds-dark-mode', msg.enabled ? '1' : '0');
                return;

            case 'SELECT_COMPONENT':
                selectComponent(msg.component, msg.instance);
                return;

            case 'CLEAR_SELECTION':
                clearSelection();
                return;

            // Preview Pipeline
            case 'APPLY_CSS_VARS':
                injectCSSVars(msg.cssVars);
                return;

            case 'CLEAR_PREVIEW_CSS':
                removePreviewCSS();
                return;

            case 'STATE_RESTORED':
                removePreviewCSS();
                if (msg.state && msg.state.cssVars) {
                    injectCSSVars(msg.state.cssVars);
                }
                return;

            case 'INIT_ACK':
                if (msg.cssVars) {
                    injectCSSVars(msg.cssVars);
                }
                return;
        }
    }

    document.addEventListener('scroll', rebuildOverlayPositions, true);
    window.addEventListener('resize', rebuildOverlayPositions, true);

    document.addEventListener('mouseover', onMouseOver, false);
    document.addEventListener('mouseout', onMouseOut, false);
    document.addEventListener('click', onClick, true);

    window.addEventListener('message', onParentMessage);

    var bodyStyle = document.createElement('style');
    bodyStyle.textContent = [
        '#phantom-se-overlay, #phantom-se-click-overlay {',
        '  contain: layout style paint;',
        '  will-change: top, left, width, height;',
        '}',
        '[data-ds-device="mobile"] {',
        '  min-width: 375px;',
        '}',
        '[data-ds-device="tablet"] {',
        '  min-width: 768px;',
        '}',
    ].join('\n');
    document.head.appendChild(bodyStyle);

    sendReady();

    window.__phantomSelectionEngineDestroy = function () {
        document.removeEventListener('scroll', rebuildOverlayPositions, true);
        window.removeEventListener('resize', rebuildOverlayPositions, true);
        document.removeEventListener('mouseover', onMouseOver, false);
        document.removeEventListener('mouseout', onMouseOut, false);
        document.removeEventListener('click', onClick, true);
        window.removeEventListener('message', onParentMessage);
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        if (clickOverlay.parentNode) clickOverlay.parentNode.removeChild(clickOverlay);
        if (bodyStyle.parentNode) bodyStyle.parentNode.removeChild(bodyStyle);
        delete window._phantomSelectionEngineInited;
        delete window.__phantomSelectionEngineDestroy;
    };

})();
