(function () {
    'use strict';

    if (window.PhantomSelectionEngine) return;
    window.PhantomSelectionEngine = true;

    var SELECTED_CLASS = 'vc-element-selected';
    var HOVER_CLASS = 'vc-element-hover';
    var STYLE_ID = 'vc-injected-styles';

    var currentSelection = null;
    var hoveredElement = null;

    var overlay = document.createElement('div');
    overlay.id = 'vc-selection-overlay';
    overlay.style.cssText = [
        'position:absolute',
        'pointer-events:none',
        'z-index:999998',
        'border:2px solid #2271b1',
        'background:rgba(34,113,177,0.06)',
        'border-radius:2px',
        'transition:all 0.15s ease',
        'display:none'
    ].join(';');
    document.body.appendChild(overlay);

    var tooltip = document.createElement('div');
    tooltip.id = 'vc-selection-tooltip';
    tooltip.style.cssText = [
        'position:absolute',
        'z-index:999999',
        'background:#1d2327',
        'color:#fff',
        'font-size:11px',
        'padding:3px 8px',
        'border-radius:3px',
        'white-space:nowrap',
        'pointer-events:none',
        'display:none',
        'font-family:-apple-system,BlinkMacSystemFont,sans-serif'
    ].join(';');
    document.body.appendChild(tooltip);

    function injectHighlightStyles() {
        if (document.getElementById(STYLE_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = [
            '.' + HOVER_CLASS + ' { outline: 2px dashed #2271b1 !important; outline-offset: 2px !important; }',
            '.' + SELECTED_CLASS + ' { outline: 2px solid #2271b1 !important; outline-offset: 2px !important; background: rgba(34,113,177,0.04) !important; }'
        ].join('\n');
        document.head.appendChild(style);
    }

    function getComponentData(el) {
        var data = {
            component: el.getAttribute('data-component') || '',
            instance: el.getAttribute('data-instance') || '',
            tokenGroup: el.getAttribute('data-token-group') || '',
            editable: (el.getAttribute('data-editable') || '').split(' ').filter(Boolean),
            source: el.getAttribute('data-source') || 'theme',
            slot: el.getAttribute('data-slot') || '',
            asset: el.getAttribute('data-asset') || '',
            state: el.getAttribute('data-state') || 'normal',
            locked: el.getAttribute('data-locked') === 'true'
        };
        return data;
    }

    function findComponentRoot(el) {
        while (el && el !== document.body) {
            if (el.hasAttribute('data-component')) return el;
            el = el.parentElement;
        }
        return null;
    }

    function updateOverlay(el) {
        if (!el) {
            overlay.style.display = 'none';
            tooltip.style.display = 'none';
            return;
        }

        var rect = el.getBoundingClientRect();
        overlay.style.left = rect.left + 'px';
        overlay.style.top = rect.top + 'px';
        overlay.style.width = rect.width + 'px';
        overlay.style.height = rect.height + 'px';
        overlay.style.display = 'block';

        var data = getComponentData(el);
        tooltip.textContent = data.component + (data.slot ? ' > ' + data.slot : '');
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - 28) + 'px';
        tooltip.style.display = 'block';
    }

    function selectElement(el) {
        if (!el) return;

        if (currentSelection) {
            currentSelection.classList.remove(SELECTED_CLASS);
        }

        currentSelection = el;
        el.classList.add(SELECTED_CLASS);
        updateOverlay(el);

        var data = getComponentData(el);

        window.parent.postMessage({
            type: 'vc-element-selected',
            data: data
        }, '*');
    }

    function clearSelection() {
        if (currentSelection) {
            currentSelection.classList.remove(SELECTED_CLASS);
            currentSelection = null;
        }
        overlay.style.display = 'none';
        tooltip.style.display = 'none';
    }

    function applyCssVars(cssVars) {
        if (!cssVars || typeof cssVars !== 'object') return;

        var styleId = 'vc-live-vars';
        var styleEl = document.getElementById(styleId);
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = styleId;
            document.head.appendChild(styleEl);
        }

        var css = ':root {';
        for (var key in cssVars) {
            if (cssVars.hasOwnProperty(key)) {
                css += key + ':' + cssVars[key] + ';';
            }
        }
        css += '}';
        styleEl.textContent = css;
    }

    document.addEventListener('mouseover', function (e) {
        var el = findComponentRoot(e.target);
        if (hoveredElement && hoveredElement !== el) {
            hoveredElement.classList.remove(HOVER_CLASS);
        }
        hoveredElement = el;
        if (el && el !== currentSelection) {
            el.classList.add(HOVER_CLASS);
        }
    }, true);

    document.addEventListener('mouseout', function (e) {
        if (hoveredElement) {
            hoveredElement.classList.remove(HOVER_CLASS);
            hoveredElement = null;
        }
    }, true);

    document.addEventListener('click', function (e) {
        var el = findComponentRoot(e.target);
        if (!el) {
            clearSelection();
            return;
        }

        var data = getComponentData(el);
        if (data.locked) {
            window.parent.postMessage({
                type: 'vc-element-locked',
                data: data
            }, '*');
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        selectElement(el);
    }, true);

    window.addEventListener('message', function (e) {
        if (!e.data || !e.data.type) return;
        switch (e.data.type) {
            case 'vc-apply-css':
                applyCssVars(e.data.cssVars);
                break;
            case 'vc-clear-selection':
                clearSelection();
                break;
            case 'vc-navigate':
                if (e.data.url) {
                    window.location.href = e.data.url;
                }
                break;
        }
    });

    injectHighlightStyles();

    window.PhantomSelection = {
        select: selectElement,
        clear: clearSelection,
        applyCss: applyCssVars,
        getCurrent: function () { return currentSelection ? getComponentData(currentSelection) : null; }
    };

    window.parent.postMessage({ type: 'vc-engine-ready' }, '*');
})();
