(function (w) {
    'use strict';

    // Homepage detection — skip dynamic injection to preserve static AETHER design
    function isHomepage() {
        var path = w.location.pathname.replace(/\/+$/, '');
        return path === '' || path === '/' || path === '/index.html' || path === '/index.php';
    }

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
            // Skip menu injection on homepage — static AETHER nav/footer is already designed
            if (isHomepage()) return;
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
            // Skip product injection on homepage — static AETHER product cards are already designed
            if (isHomepage()) return;
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
