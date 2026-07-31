(function () {
    'use strict';

    if (window.PhantomSelectionEngine) return;
    window.PhantomSelectionEngine = true;

    var SELECTED_CLASS = 'vc-element-selected';
    var HOVER_CLASS = 'vc-element-hover';
    var STYLE_ID = 'vc-injected-styles';

    var currentSelection = null;
    var hoveredElement = null;
    var editingEnabled = false;
    var lastPartData = { part: '', partLabel: '' };

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

    var PAGE_LABELS = {
        'home-page': 'Homepage',
        'shop-page': 'Shop',
        'blog-page': 'Blog',
        'about-page': 'About',
        'contact-page': 'Contact',
        'search-page': 'Search',
        'wishlist-page': 'Wishlist',
        'cart-page': 'Cart',
        'checkout-page': 'Checkout',
        'product-page': 'Product',
        'login-page': 'Login',
        'register-page': 'Register',
        'orders-page': 'Orders',
        'order-detail-page': 'Order Detail',
        'thankyou-page': 'Thank You',
        '404-page': 'Not Found'
    };

    function humanize(value) {
        return String(value || '')
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, function (c) { return c.toUpperCase(); })
            .trim();
    }

    function getPageLabel() {
        var cls = (document.body.className || '').split(/\s+/).filter(function (c) { return /-page$/.test(c); })[0];
        if (cls && PAGE_LABELS[cls]) return PAGE_LABELS[cls];
        if (cls) return humanize(cls);
        return 'Page';
    }

    function findPartElement(target, root) {
        var el = target;
        while (el && el !== document.body) {
            if (el.hasAttribute && el.hasAttribute('data-part')) return el;
            if (el === root) break;
            el = el.parentElement;
        }
        return null;
    }

    function getPartData(target, root) {
        var partEl = findPartElement(target, root);
        if (!partEl) return { part: '', partLabel: '' };
        return {
            part: partEl.getAttribute('data-part') || '',
            partLabel: partEl.getAttribute('data-part-label') || humanize(partEl.getAttribute('data-part'))
        };
    }

    function buildBreadcrumb(componentData, partData) {
        var crumbs = [getPageLabel(), humanize(componentData.component)];
        if (partData && partData.part) {
            crumbs.push(partData.partLabel || humanize(partData.part));
        }
        return crumbs.join(' \u203A ');
    }

    function findComponentRoot(el) {
        while (el && el !== document.body) {
            if (el.hasAttribute('data-component')) return el;
            el = el.parentElement;
        }
        return null;
    }

    function updateOverlay(el, target) {
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
        var partData = getPartData(target || el, el);
        tooltip.textContent = buildBreadcrumb(data, partData);
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - 28) + 'px';
        tooltip.style.display = 'block';
    }

    function selectElement(el, target) {
        if (!el) return;

        if (currentSelection) {
            currentSelection.classList.remove(SELECTED_CLASS);
        }

        currentSelection = el;
        el.classList.add(SELECTED_CLASS);
        updateOverlay(el, target || el);

        var data = getComponentData(el);
        var partData = getPartData(target || el, el);
        lastPartData = partData;
        data.part = partData.part;
        data.partLabel = partData.partLabel;
        data.breadcrumb = buildBreadcrumb(data, partData);
        window.parent.postMessage({
            type: 'vc-element-selected',
            data: data
        }, '*');
    }

    function setEditingMode(enabled) {
        editingEnabled = !!enabled;
        if (!editingEnabled) {
            clearSelection();
        }
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
        if (!editingEnabled) return;
        var el = findComponentRoot(e.target);
        if (hoveredElement && hoveredElement !== el) {
            hoveredElement.classList.remove(HOVER_CLASS);
        }
        hoveredElement = el;
        if (el && el !== currentSelection) {
            el.classList.add(HOVER_CLASS);
        }
        updateOverlay(el, e.target);
    }, true);

    document.addEventListener('mouseout', function (e) {
        if (hoveredElement) {
            hoveredElement.classList.remove(HOVER_CLASS);
            hoveredElement = null;
        }
    }, true);

    document.addEventListener('click', function (e) {
        if (!editingEnabled) return;
        var el = findComponentRoot(e.target);
        if (!el) {
            clearSelection();
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        var data = getComponentData(el);
        if (data.locked) {
            window.parent.postMessage({
                type: 'vc-element-locked',
                data: data
            }, '*');
            return;
        }

        selectElement(el, e.target);
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
            case 'vc-editing-mode':
                setEditingMode(e.data.enabled);
                break;
        }
    });

    injectHighlightStyles();

    window.PhantomSelection = {
        select: selectElement,
        clear: clearSelection,
        applyCss: applyCssVars,
        getCurrent: function () {
            if (!currentSelection) return null;
            var data = getComponentData(currentSelection);
            data.part = lastPartData.part;
            data.partLabel = lastPartData.partLabel;
            data.breadcrumb = buildBreadcrumb(data, lastPartData);
            return data;
        }
    };

    window.parent.postMessage({ type: 'vc-engine-ready' }, '*');
})();
