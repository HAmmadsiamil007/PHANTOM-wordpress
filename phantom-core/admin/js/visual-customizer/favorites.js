(function ($) {
    'use strict';

    var FavoritesEngine = {
        items: [],
        listContainer: null
    };

    function init() {
        FavoritesEngine.listContainer = document.getElementById('vc-favorites-list');
        if (!FavoritesEngine.listContainer) return;

        loadFavorites();
        bindToggleEvents();
        bindRemoveEvents();
    }

    function loadFavorites() {
        $.ajax({
            url: PhantomFav.restUrl + '/favorites',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomFav.nonce);
            },
            success: function (resp) {
                FavoritesEngine.items = resp.favorites || [];
                renderFavorites();
            },
            error: function () {
                FavoritesEngine.listContainer.innerHTML =
                    '<div class="vc-favorites-empty">Failed to load favorites.</div>';
            }
        });
    }

    function renderFavorites() {
        if (!FavoritesEngine.listContainer) return;

        if (FavoritesEngine.items.length === 0) {
            FavoritesEngine.listContainer.innerHTML =
                '<div class="vc-favorites-empty">No favorites yet. Click the star icon next to any component to add it.</div>';
            return;
        }

        var html = '<div class="vc-favorites-list">';
        FavoritesEngine.items.forEach(function (fav) {
            var key = fav.key || fav.type + ':' + fav.id;
            var label = fav.label || fav.id;
            var category = fav.category || '';

            html += '<div class="vc-fav-item" data-key="' + escapeHtml(key) + '" data-type="' + escapeHtml(fav.type) + '" data-id="' + escapeHtml(fav.id) + '">';
            html += '<span class="dashicons dashicons-star-filled vc-fav-icon"></span>';
            html += '<span class="vc-fav-label">' + escapeHtml(label) + '</span>';
            if (category) {
                html += '<span class="vc-fav-category">' + escapeHtml(category) + '</span>';
            }
            html += '<button type="button" class="vc-fav-remove" title="Remove from favorites">';
            html += '<span class="dashicons dashicons-no-alt"></span>';
            html += '</button>';
            html += '</div>';
        });
        html += '</div>';
        FavoritesEngine.listContainer.innerHTML = html;
    }

    function toggleFavorite(type, id, btn) {
        $.ajax({
            url: PhantomFav.restUrl + '/favorites/toggle',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PhantomFav.nonce);
            },
            data: { type: type, id: id },
            success: function (resp) {
                if (resp.success) {
                    var isActive = resp.favorite.active;
                    var key = type + ':' + id;
                    if (isActive) {
                        if (FavoritesEngine.items.indexOf(key) === -1) {
                            FavoritesEngine.items.push({
                                key: key,
                                type: type,
                                id: id,
                                label: btn ? btn.closest('.vc-header-title')?.querySelector('h3')?.textContent || id : id
                            });
                        }
                    } else {
                        FavoritesEngine.items = FavoritesEngine.items.filter(function (f) {
                            return (f.key || f.type + ':' + f.id) !== key;
                        });
                    }
                    if (btn) {
                        btn.classList.toggle('active', isActive);
                    }
                    renderFavorites();
                    showNotice(isActive ? 'Added to favorites' : 'Removed from favorites', 'info');
                }
            },
            error: function () {
                showNotice('Failed to toggle favorite.', 'error');
            }
        });
    }

    function bindToggleEvents() {
        $(document).on('click', '.vc-fav-btn', function () {
            var btn = $(this);
            var type = btn.data('type');
            var id = btn.data('id');
            toggleFavorite(type, id, btn[0]);
        });
    }

    function bindRemoveEvents() {
        $(document).on('click', '.vc-fav-remove', function () {
            var item = $(this).closest('.vc-fav-item');
            var type = item.data('type');
            var id = item.data('id');
            toggleFavorite(type, id, null);
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function showNotice(message, type) {
        if (typeof window.showNotice === 'function') {
            window.showNotice(message, type);
            return;
        }
        var types = {
            success: '#edfaef',
            error: '#fcf0f1',
            warning: '#fef8ee',
            info: '#f0f6fc'
        };
        var colors = {
            success: '#2c6b3e',
            error: '#b32d2e',
            warning: '#996800',
            info: '#2271b1'
        };
        var notice = $('<div class="vc-notice" style="position:fixed;bottom:16px;right:16px;padding:12px 16px;border-radius:4px;font-size:13px;z-index:99999;background:' + (types[type] || '#fff') + ';border-left:4px solid ' + (colors[type] || '#2271b1') + ';box-shadow:0 2px 8px rgba(0,0,0,0.15);max-width:350px;">' + message + '</div>');
        $('body').append(notice);
        setTimeout(function () {
            notice.fadeOut(300, function () { notice.remove(); });
        }, 3000);
    }

    $(document).ready(init);
})(jQuery);
