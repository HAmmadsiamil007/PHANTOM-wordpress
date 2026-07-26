# T0.6 — Create PhantomInjector.js

**Goal:** Create PhantomInjector JS implementation and wire into phantom-core.js.

## Files to Create

### `phantom-core/frontend/assets/js/phantom-injector.js`
```javascript
(function (w) {
    'use strict';

    var PhantomInjector = {
        injectContent: function (element, data) {
            if (!element || !data) return;
            var html = element.innerHTML;
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                    html = html.replace(pattern, data[key]);
                }
            }
            element.innerHTML = html;
        },

        injectAttributes: function (element, data) {
            if (!element || !data) return;
            var attrs = element.attributes;
            for (var i = 0; i < attrs.length; i++) {
                var attr = attrs[i];
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                        attr.value = attr.value.replace(pattern, data[key]);
                    }
                }
            }
        },

        renderComponent: function (container, template, data) {
            if (!container || !template) return;
            var html = template;
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                    html = html.replace(pattern, data[key]);
                }
            }
            container.innerHTML = html;
        },

        injectSettings: function (settings) {
            if (!settings) return;
            var els = document.querySelectorAll('[data-phantom-setting]');
            for (var i = 0; i < els.length; i++) {
                var el = els[i];
                var key = el.getAttribute('data-phantom-setting');
                if (settings[key] !== undefined) {
                    if (el.tagName === 'IMG') {
                        el.src = settings[key];
                    } else {
                        el.textContent = settings[key];
                    }
                }
            }
        },

        injectMenus: function (menus) {
            if (!menus) return;
            for (var location in menus) {
                if (menus.hasOwnProperty(location)) {
                    var container = document.querySelector('[data-phantom-menu="' + location + '"]');
                    if (!container) continue;
                    var items = menus[location].items || [];
                    var html = '';
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        html += '<li class="nav-item">';
                        html += '<a href="' + (item.url || '#') + '" class="nav-link">';
                        html += item.title || '';
                        html += '</a></li>';
                    }
                    container.innerHTML = html;
                }
            }
        },

        injectProducts: function (products) {
            if (!products) return;
            var containers = document.querySelectorAll('[data-phantom-products]');
            for (var c = 0; c < containers.length; c++) {
                var container = containers[c];
                var template = container.getAttribute('data-phantom-template') || '';
                var html = '';
                for (var i = 0; i < products.length; i++) {
                    var product = products[i];
                    var itemHtml = template;
                    for (var key in product) {
                        if (product.hasOwnProperty(key)) {
                            var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                            itemHtml = itemHtml.replace(pattern, product[key] || '');
                        }
                    }
                    html += itemHtml;
                }
                container.innerHTML = html;
            }
        }
    };

    w.PhantomInjector = PhantomInjector;
})(window);
```

## Files to Modify

### `phantom-core/frontend/assets/js/phantom-core.js`
Read the existing file first. Look for any references to `PhantomInjector &&` (guard pattern like `w.PhantomInjector && w.PhantomInjector.injectSettings(...)`). Replace those guard no-ops with actual function calls.

The existing phantom-core.js likely has some initialization code. Add this at the end (before any existing init calls or after them):

```javascript
// Initialize PhantomInjector with data from PhantomData
(function () {
    if (!window.PhantomInjector) return;

    if (window.PhantomData) {
        if (window.PhantomData.settings) {
            window.PhantomInjector.injectSettings(window.PhantomData.settings);
        }
        if (window.PhantomData.menus) {
            window.PhantomInjector.injectMenus(window.PhantomData.menus);
        }
        if (window.PhantomData.products) {
            window.PhantomInjector.injectProducts(window.PhantomData.products);
        }
    }
})();
```

And replace any existing `w.PhantomInjector && w.PhantomInjector.injectSettings(...)` calls (the guard no-ops) with the actual call from the init block above.

## Verification
```bash
# Check that PhantomInjector appears in the file
grep -c "PhantomInjector" phantom-core/frontend/assets/js/phantom-injector.js
```
Expected: > 0

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress && git add phantom-core/frontend/assets/js/phantom-injector.js phantom-core/frontend/assets/js/phantom-core.js && git commit -m "feat(phase0): create PhantomInjector.js with DOM injection API, wire into phantom-core.js"
```
