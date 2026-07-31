(function ($) {
    'use strict';

    var SearchEngine = {
        input: null,
        results: null,
        selectedIndex: -1,
        debounceTimer: null,
        lastQuery: ''
    };

    function init() {
        SearchEngine.input = document.getElementById('vc-global-search');
        if (!SearchEngine.input) return;

        SearchEngine.results = document.getElementById('vc-search-results');

        SearchEngine.input.addEventListener('input', onInput);
        SearchEngine.input.addEventListener('keydown', onKeyDown);
        document.addEventListener('click', onClickOutside);

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                SearchEngine.input.focus();
            }
        });
    }

    function onInput() {
        var q = SearchEngine.input.value.trim();
        clearTimeout(SearchEngine.debounceTimer);

        if (q.length < 2) {
            closeResults();
            return;
        }

        SearchEngine.debounceTimer = setTimeout(function () {
            fetchResults(q);
        }, 200);
    }

    function fetchResults(q) {
        if (q === SearchEngine.lastQuery) return;
        SearchEngine.lastQuery = q;
        SearchEngine.selectedIndex = -1;

        $.ajax({
            url: PhantomSearch.restUrl + '/search',
            method: 'GET',
            data: { q: q },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomSearch.nonce);
            },
            success: function (resp) {
                renderResults(resp.results || []);
            },
            error: function () {
                SearchEngine.results.innerHTML =
                    '<div class="vc-search-error">Search failed. Check REST API.</div>';
                SearchEngine.results.classList.add('open');
            }
        });
    }

    function renderResults(results) {
        SearchEngine.results.innerHTML = '';
        SearchEngine.results.classList.remove('open');

        if (!results || results.length === 0) {
            SearchEngine.results.innerHTML =
                '<div class="vc-search-empty">No results found.</div>';
            SearchEngine.results.classList.add('open');
            return;
        }

        var grouped = {};
        results.forEach(function (item) {
            var cat = item.type || 'general';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(item);
        });

        var categoryLabels = {
            component: 'Components',
            instance: 'Instances',
            property: 'Properties',
            token: 'Design Tokens',
            asset: 'Assets',
            animation: 'Animations',
            setting: 'Settings'
        };

        Object.keys(grouped).forEach(function (cat) {
            var label = categoryLabels[cat] || cat.charAt(0).toUpperCase() + cat.slice(1) + 's';
            var header = document.createElement('div');
            header.className = 'vc-search-category';
            header.textContent = label;
            SearchEngine.results.appendChild(header);

            grouped[cat].forEach(function (item) {
                var el = document.createElement('div');
                el.className = 'vc-search-item';
                el.dataset.type = item.type;
                el.dataset.id = item.id;

                var labelSpan = document.createElement('span');
                labelSpan.className = 'vc-search-item-label';
                labelSpan.textContent = item.label;
                el.appendChild(labelSpan);

                if (item.description) {
                    var descSpan = document.createElement('span');
                    descSpan.className = 'vc-search-item-desc';
                    descSpan.textContent = item.description;
                    el.appendChild(descSpan);
                }

                el.addEventListener('click', function () {
                    selectItem(item);
                });

                SearchEngine.results.appendChild(el);
            });
        });

        SearchEngine.results.classList.add('open');
    }

    function onKeyDown(e) {
        var items = SearchEngine.results.querySelectorAll('.vc-search-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            SearchEngine.selectedIndex = Math.min(SearchEngine.selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            SearchEngine.selectedIndex = Math.max(SearchEngine.selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter' && SearchEngine.selectedIndex >= 0) {
            e.preventDefault();
            items[SearchEngine.selectedIndex].click();
        } else if (e.key === 'Escape') {
            closeResults();
            SearchEngine.input.blur();
        }
    }

    function updateSelection(items) {
        items.forEach(function (el, i) {
            el.classList.toggle('vc-search-item--selected', i === SearchEngine.selectedIndex);
        });
        if (SearchEngine.selectedIndex >= 0 && items[SearchEngine.selectedIndex]) {
            items[SearchEngine.selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    function selectItem(item) {
        closeResults();
        SearchEngine.input.value = '';

        if (item.type === 'component') {
            if (window.VC) {
                window.VC.selectedComponent = item.id;
                window.VC.selectedInstance = item.id + '.' + Date.now();
                if (typeof window.showSidebarContent === 'function') window.showSidebarContent();
                if (typeof window.renderInspector === 'function') window.renderInspector(item.id, window.VC.selectedInstance);
            }
        } else if (item.type === 'instance') {
            if (window.VC) {
                var comp = item.id.split(/[.\-]/)[0];
                window.VC.selectedComponent = comp;
                window.VC.selectedInstance = item.id;
                if (typeof window.showSidebarContent === 'function') window.showSidebarContent();
                if (typeof window.renderInspector === 'function') window.renderInspector(comp, item.id);
            }
        }
    }

    function closeResults() {
        if (SearchEngine.results) {
            SearchEngine.results.classList.remove('open');
            SearchEngine.results.innerHTML = '';
        }
        SearchEngine.selectedIndex = -1;
        SearchEngine.lastQuery = '';
    }

    function onClickOutside(e) {
        if (SearchEngine.input && !SearchEngine.input.parentElement.contains(e.target)) {
            closeResults();
        }
    }

    $(document).ready(init);
})(jQuery);
