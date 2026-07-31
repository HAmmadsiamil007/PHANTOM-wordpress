(function ($) {
    'use strict';

    if (window.PhantomInlineEditor) return;
    window.PhantomInlineEditor = true;

    var InlineEditor = {
        activeElement: null,
        originalContent: null,
        isEditing: false
    };

    var STYLE_ID = 'vc-inline-editor-styles';

    function injectStyles() {
        if (document.getElementById(STYLE_ID)) return;
        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = [
            '[data-editable] { cursor: text; }',
            '[data-editable]:hover { outline: 1px dashed #2271b1; outline-offset: 2px; }',
            '[data-editable].vc-editing { outline: 2px solid #2271b1; outline-offset: 2px; background: rgba(34,113,177,0.04); }',
            '[data-editable].vc-editing:focus { outline: 2px solid #135e96; }',
            '[data-locked="true"] { cursor: not-allowed; opacity: 0.85; }',
            '[data-locked="true"]:hover { outline: 1px dashed #b32d2e; outline-offset: 2px; }',
            '.vc-lock-badge-preview { position:absolute; top:-20px; right:0; background:#b32d2e; color:#fff; font-size:10px; padding:1px 6px; border-radius:2px; white-space:nowrap; z-index:99999; pointer-events:none; }',
            '.vc-state-indicator { position:absolute; top:-20px; left:0; background:#2271b1; color:#fff; font-size:10px; padding:1px 6px; border-radius:2px; white-space:nowrap; z-index:99999; pointer-events:none; }'
        ].join('\n');
        document.head.appendChild(style);
    }

    function getComponentRoot(el) {
        while (el && el !== document.body) {
            if (el.hasAttribute('data-component')) return el;
            el = el.parentElement;
        }
        return null;
    }

    function isEditable(el) {
        return el && el.hasAttribute('data-editable') && el.getAttribute('data-editable') !== '';
    }

    function isLocked(el) {
        return el && el.getAttribute('data-locked') === 'true';
    }

    function startInlineEdit(el) {
        if (!isEditable(el) || isLocked(el)) return;

        InlineEditor.activeElement = el;
        InlineEditor.originalContent = el.innerHTML;
        InlineEditor.isEditing = true;

        el.contentEditable = 'true';
        el.classList.add('vc-editing');
        el.focus();

        selectAllText(el);

        el.addEventListener('blur', finishInlineEdit);
        el.addEventListener('keydown', handleInlineEditKeydown);
    }

    function finishInlineEdit() {
        if (!InlineEditor.activeElement) return;

        var el = InlineEditor.activeElement;
        el.contentEditable = 'false';
        el.classList.remove('vc-editing');
        el.removeEventListener('blur', finishInlineEdit);
        el.removeEventListener('keydown', handleInlineEditKeydown);

        var newContent = el.innerHTML;
        if (newContent !== InlineEditor.originalContent) {
            notifyContentChange(el, newContent);
        }

        InlineEditor.activeElement = null;
        InlineEditor.originalContent = null;
        InlineEditor.isEditing = false;
    }

    function handleInlineEditKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            InlineEditor.activeElement.blur();
        }
        if (e.key === 'Escape') {
            if (InlineEditor.activeElement) {
                InlineEditor.activeElement.innerHTML = InlineEditor.originalContent;
            }
            InlineEditor.activeElement.blur();
        }
    }

    function selectAllText(el) {
        var range = document.createRange();
        range.selectNodeContents(el);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }

    function notifyContentChange(el, content) {
        var data = {
            component: el.getAttribute('data-component') || '',
            instance: el.getAttribute('data-instance') || '',
            editable: (el.getAttribute('data-editable') || '').split(' ').filter(Boolean),
            content: content
        };

        window.parent.postMessage({
            type: 'vc-inline-content-changed',
            data: data
        }, '*');
    }

    function applyColorChange(property, value) {
        if (!InlineEditor.activeElement) return;
        InlineEditor.activeElement.style.setProperty(property, value);

        window.parent.postMessage({
            type: 'vc-apply-css',
            cssVars: (function () {
                var vars = {};
                vars['--' + property] = value;
                return vars;
            })()
        }, '*');
    }

    function applyPropertyChange(property, value) {
        if (!InlineEditor.activeElement) return;
        InlineEditor.activeElement.style.setProperty(property, value);
    }

    function switchState(state) {
        if (InlineEditor.activeElement) {
            InlineEditor.activeElement.setAttribute('data-state', state);
        }

        var els = document.querySelectorAll('[data-component]');
        els.forEach(function (el) {
            el.classList.remove('vc-state-hover', 'vc-state-focus', 'vc-state-active', 'vc-state-disabled', 'vc-state-visited');
            if (state !== 'normal') {
                el.classList.add('vc-state-' + state);
            }
        });
    }

    function switchViewport(viewport) {
        var frame = document.getElementById('vc-preview-iframe');
        if (!frame) return;

        var widths = {
            desktop: '100%',
            tablet: '768px',
            mobile: '375px'
        };

        frame.style.width = widths[viewport] || '100%';
        frame.style.margin = viewport !== 'desktop' ? '0 auto' : '0';
    }

    function showLockBadge(el) {
        var existing = el.querySelector('.vc-lock-badge-preview');
        if (existing) return;

        var badge = document.createElement('div');
        badge.className = 'vc-lock-badge-preview';
        badge.textContent = '\u{1F512} Locked';
        el.style.position = 'relative';
        el.appendChild(badge);
    }

    function removeLockBadge(el) {
        var badge = el.querySelector('.vc-lock-badge-preview');
        if (badge) badge.remove();
    }

    document.addEventListener('click', function (e) {
        var el = e.target;
        if (InlineEditor.isEditing) {
            return;
        }
        if (isEditable(el) && !isLocked(el)) {
            e.preventDefault();
            e.stopPropagation();
            startInlineEdit(el);
        }
    }, true);

    document.addEventListener('mouseover', function (e) {
        var el = e.target;
        if (!el || InlineEditor.isEditing) return;

        if (isLocked(el) && el.hasAttribute('data-component')) {
            showLockBadge(el);
        }
    }, true);

    document.addEventListener('mouseout', function (e) {
        var el = e.target;
        if (!el) return;
        removeLockBadge(el);
    }, true);

    window.addEventListener('message', function (e) {
        if (!e.data || !e.data.type) return;

        switch (e.data.type) {
            case 'vc-state-change':
                switchState(e.data.state);
                break;

            case 'vc-viewport-change':
                switchViewport(e.data.viewport);
                break;

            case 'vc-apply-color':
                applyColorChange(e.data.property, e.data.value);
                break;

            case 'vc-apply-property':
                applyPropertyChange(e.data.property, e.data.value);
                break;
        }
    });

    injectStyles();

    window.PhantomInline = {
        startEdit: startInlineEdit,
        finishEdit: finishInlineEdit,
        switchState: switchState,
        switchViewport: switchViewport,
        applyColor: applyColorChange,
        applyProperty: applyPropertyChange,
        isEditing: function () { return InlineEditor.isEditing; }
    };

    window.parent.postMessage({ type: 'vc-inline-editor-ready' }, '*');
})(jQuery);
