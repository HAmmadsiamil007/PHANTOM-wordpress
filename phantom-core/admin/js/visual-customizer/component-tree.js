(function ($) {
    'use strict';

    var TreeEngine = {
        container: null,
        filterInput: null,
        lastData: null,
        favorites: []
    };

    function init() {
        TreeEngine.container = document.getElementById('vc-instance-tree');
        if (!TreeEngine.container) return;

        TreeEngine.filterInput = document.getElementById('vc-tree-filter');

        loadTree();
        bindEvents();
    }

    function loadTree() {
        $.ajax({
            url: PhantomVC.restUrl + '/instances/tree',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomVC.nonce);
            },
            success: function (resp) {
                TreeEngine.lastData = resp.tree || [];
                renderTree(TreeEngine.lastData);
            },
            error: function () {
                TreeEngine.container.innerHTML =
                    '<div class="vc-tree-empty">Failed to load component tree.</div>';
            }
        });
    }

    function renderTree(treeData) {
        TreeEngine.container.innerHTML = '';

        if (!treeData || treeData.length === 0) {
            TreeEngine.container.innerHTML =
                '<div class="vc-tree-empty">No instances yet. Click elements in preview to create them.</div>';
            return;
        }

        var filter = TreeEngine.filterInput ? TreeEngine.filterInput.value.trim().toLowerCase() : '';
        var filtered = filter ? filterNodes(treeData, filter) : treeData;

        var ul = document.createElement('ul');
        ul.className = 'vc-tree';
        filtered.forEach(function (node) {
            ul.appendChild(createTreeNode(node, 0));
        });
        TreeEngine.container.appendChild(ul);
    }

    function createTreeNode(node, depth) {
        var li = document.createElement('li');
        li.className = 'vc-tree-node' + (node.locked ? ' is-locked' : '') + ' vc-tree-depth-' + depth;
        li.dataset.instanceId = node.id;
        li.dataset.component = node.component;

        var label = document.createElement('div');
        label.className = 'vc-tree-node-label';

        if (node.children && node.children.length > 0) {
            var toggle = document.createElement('span');
            toggle.className = 'vc-tree-toggle dashicons dashicons-arrow-down';
            label.appendChild(toggle);
        } else {
            var emptyToggle = document.createElement('span');
            emptyToggle.className = 'vc-tree-toggle vc-tree-toggle--empty';
            label.appendChild(emptyToggle);
        }

        if (node.locked) {
            var lock = document.createElement('span');
            lock.className = 'vc-tree-locked dashicons dashicons-lock';
            lock.title = 'Locked';
            label.appendChild(lock);
        }

        var text = document.createElement('span');
        text.textContent = node.label || node.id;
        label.appendChild(text);

        var badges = document.createElement('span');
        badges.className = 'vc-tree-badges';
        if (node.has_state_overrides) {
            var sb = document.createElement('span');
            sb.className = 'vc-tree-badge vc-badge-state';
            sb.textContent = 'S';
            sb.title = 'Has state overrides';
            badges.appendChild(sb);
        }
        if (node.has_viewport_overrides) {
            var vb = document.createElement('span');
            vb.className = 'vc-tree-badge vc-badge-viewport';
            vb.textContent = 'V';
            vb.title = 'Has viewport overrides';
            badges.appendChild(vb);
        }
        if (node.override_count > 0) {
            var mb = document.createElement('span');
            mb.className = 'vc-tree-badge vc-badge-modified';
            mb.textContent = node.override_count;
            mb.title = 'Modified properties';
            badges.appendChild(mb);
        }
        label.appendChild(badges);

        label.addEventListener('click', function (e) {
            if (e.target.closest('.vc-tree-toggle')) {
                li.classList.toggle('collapsed');
                return;
            }
            selectInstance(node.id, node.component);
        });

        li.appendChild(label);

        if (node.children && node.children.length > 0) {
            var childUl = document.createElement('ul');
            childUl.className = 'vc-tree-children';
            node.children.forEach(function (child) {
                childUl.appendChild(createTreeNode(child, depth + 1));
            });
            li.appendChild(childUl);
        }

        return li;
    }

    function selectInstance(instanceId, component) {
        if (window.VC) {
            window.VC.selectedComponent = component;
            window.VC.selectedInstance = instanceId;
            if (typeof window.showSidebarContent === 'function') window.showSidebarContent();
            if (typeof window.renderInspector === 'function') window.renderInspector(component, instanceId);
        }
    }

    function filterNodes(nodes, filter) {
        var result = [];
        nodes.forEach(function (node) {
            var match = (node.label || node.id || '').toLowerCase().indexOf(filter) !== -1;
            var filteredChildren = node.children ? filterNodes(node.children, filter) : [];
            if (match || filteredChildren.length > 0) {
                result.push({
                    id: node.id,
                    component: node.component,
                    label: node.label,
                    locked: node.locked,
                    override_count: node.override_count,
                    has_state_overrides: node.has_state_overrides,
                    has_viewport_overrides: node.has_viewport_overrides,
                    children: filteredChildren
                });
            }
        });
        return result;
    }

    function bindEvents() {
        TreeEngine.container.addEventListener('click', function (e) {
            var toggle = e.target.closest('.vc-tree-toggle');
            if (toggle && !toggle.classList.contains('vc-tree-toggle--empty')) {
                var node = toggle.closest('.vc-tree-node');
                if (node) node.classList.toggle('collapsed');
            }
        });

        if (TreeEngine.filterInput) {
            TreeEngine.filterInput.addEventListener('input', function () {
                renderTree(TreeEngine.lastData);
            });
        }
    }

    function refresh() {
        loadTree();
    }

    $(document).ready(init);

    window.PhantomComponentTree = {
        refresh: refresh,
        load: loadTree
    };
})(jQuery);
