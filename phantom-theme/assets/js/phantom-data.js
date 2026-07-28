/**
 * Phantom Theme Data Bridge v2.0
 * Delegates to plugin's modular PhantomServices system.
 * Keeps only theme-specific enhancements (360 viewer, image zoom).
 */
(function(w) {
  'use strict';
  if (w.__PhantomThemeDataInited) return;
  w.__PhantomThemeDataInited = true;

  var PD = w.PhantomData || {};
  var apiBase = PD.rest_url ? PD.rest_url.replace(/\/+$/, '') + '/phantom/v1'
    : (w.wpApiSettings ? w.wpApiSettings.root.replace(/\/+$/, '') + '/phantom/v1'
      : '/index.php?rest_route=/phantom/v1');

  function fetchJSON(path, opts) {
    opts = opts || {};
    var method = opts.method || 'GET';
    var body = opts.body;
    var timeout = opts.timeout || 10000;
    var controller = new AbortController();
    var timer = setTimeout(function() { controller.abort(); }, timeout);
    var qIdx = path.indexOf('?');
    var url;
    if (qIdx === -1) { url = apiBase + path; }
    else {
      var baseHasQuery = apiBase.indexOf('?') !== -1;
      url = apiBase + path.substring(0, qIdx) + (baseHasQuery ? '&' : '?') + path.substring(qIdx + 1);
    }
    var fetchOpts = {
      method: method,
      signal: controller.signal,
      credentials: 'same-origin'
    };
    if (method !== 'GET') {
      var nonce = PD.api_nonce || (w.PhantomData && w.PhantomData.api_nonce) || '';
      fetchOpts.headers = { 'Content-Type': 'application/json', 'X-Phantom-Nonce': nonce };
      if (body) fetchOpts.body = JSON.stringify(body);
    }
    return fetch(url, fetchOpts).then(function(r) {
      clearTimeout(timer);
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    }).then(function(data) {
      return data;
    }).catch(function(err) {
      clearTimeout(timer);
      throw err;
    });
  }

  w.PhantomCore = w.PhantomCore || { fetchJSON: fetchJSON, apiBase: apiBase };

  function injectSettings(settings) {
    if (!settings) return;
    document.querySelectorAll('[data-phantom]').forEach(function(el) {
      var key = el.getAttribute('data-phantom');
      if (settings[key] !== undefined) {
        if (el.tagName === 'IMG') { el.src = settings[key]; }
        else { el.textContent = settings[key]; }
      }
    });
    document.querySelectorAll('[data-phantom-bg]').forEach(function(el) {
      var key = el.getAttribute('data-phantom-bg');
      if (settings[key]) { el.style.backgroundImage = 'url(' + settings[key] + ')'; }
    });
  }

  function buildMenuHTML(items) {
    if (!items || !items.length) return '';
    var html = '';
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var hasChildren = item.children && item.children.length > 0;
      html += '<li class="nav-item' + (hasChildren ? ' dropdown' : '') + '">';
      html += '<a class="nav-link' + (hasChildren ? ' dropdown-toggle' : '') + '" href="' + (item.url || '#') + '"' + (hasChildren ? ' role="button" data-bs-toggle="dropdown"' : '') + '>' + (item.title || '') + '</a>';
      if (hasChildren) {
        html += '<ul class="dropdown-menu">';
        for (var j = 0; j < item.children.length; j++) {
          var child = item.children[j];
          html += '<li><a class="dropdown-item" href="' + (child.url || '#') + '">' + (child.title || '') + '</a></li>';
        }
        html += '</ul>';
      }
      html += '</li>';
    }
    return html;
  }

  function injectMenus(menus) {
    if (!menus) return;
    document.querySelectorAll('[data-phantom-menu]').forEach(function(el) {
      var location = el.getAttribute('data-phantom-menu');
      var menu = menus[location];
      if (menu && menu.items) {
        el.innerHTML = buildMenuHTML(menu.items);
      }
    });
  }

  function injectProducts(products, settings) {
    if (!products || !products.length) return;
    document.querySelectorAll('[data-phantom-products]').forEach(function(container) {
      var count = parseInt(container.getAttribute('data-phantom-products'), 10) || products.length;
      var items = products.slice(0, count);
      container.innerHTML = '';
      items.forEach(function(p) {
        var imgSrc = p.image || '';
        var priceHtml = p.price_html || '<span class="price">$' + (p.price || '0') + '</span>';
        var isSale = p.on_sale;
        var isFeatured = p.is_featured;
        var salePrice = p.sale_price || '';
        var regPrice = p.regular_price || p.price || '';
        var catMode = settings ? !!+settings.shop_catalog_mode : false;
        var wlEnabled = settings ? !!+settings.shop_wishlist_enable : false;
        var qvEnabled = settings ? !!+settings.card_quick_view : false;

        var div = document.createElement('div');
        div.className = 'col-lg-3 col-md-4 col-sm-6 mb-4';
        div.innerHTML = '<div class="product-card">' +
          '<div class="product-img">' +
          (isSale ? '<span class="sale-badge">Sale!</span>' : '') +
          (isFeatured ? '<span class="featured-badge">Featured</span>' : '') +
          '<a href="' + (p.permalink || '#') + '">' +
          '<img src="' + imgSrc + '" alt="' + (p.name || '') + '" class="img-fluid">' +
          '</a>' +
          '<div class="product-actions">' +
          (catMode ? '' : '<a href="#" class="add-to-cart-trigger" data-product_id="' + (p.id || '') + '"><i class="fas fa-shopping-cart"></i></a>') +
          (wlEnabled ? '<a href="#" class="phantom-wishlist-trigger" data-product-id="' + (p.id || '') + '"><i class="far fa-heart"></i></a>' : '') +
          (qvEnabled ? '<a href="#" class="phantom-quickview-trigger" data-product-id="' + (p.id || '') + '"><i class="fas fa-eye"></i></a>' : '') +
          '</div></div>' +
          '<div class="product-content">' +
          '<h6><a href="' + (p.permalink || '#') + '">' + (p.name || '') + '</a></h6>' +
          '<div class="product-price">' + priceHtml + '</div>' +
          '</div></div>';
        container.appendChild(div);
      });
    });
  }

  function injectPosts(posts) {
    if (!posts || !posts.length) return;
    document.querySelectorAll('[data-phantom-posts]').forEach(function(container) {
      var count = parseInt(container.getAttribute('data-phantom-posts'), 10) || posts.length;
      var items = posts.slice(0, count);
      container.innerHTML = '';
      items.forEach(function(post) {
        var imgSrc = post.featured_image || 'assets/images/single-blog-tab-img1.jpg';
        var dateStr = post.date ? new Date(post.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '';
        var div = document.createElement('div');
        div.className = 'col-lg-6 col-md-6 mb-4';
        div.innerHTML = '<div class="blog-card">' +
          '<figure><a href="' + (post.permalink || '#') + '"><img src="' + imgSrc + '" alt="' + (post.title || '') + '" class="img-fluid"></a></figure>' +
          '<div class="blog-content">' +
          '<ul class="blog-meta"><li><i class="far fa-calendar"></i> ' + dateStr + '</li></ul>' +
          '<h4><a href="' + (post.permalink || '#') + '">' + (post.title || '') + '</a></h4>' +
          '<p>' + (post.excerpt || '') + '</p>' +
          '<a href="' + (post.permalink || '#') + '" class="read-more">Read More</a>' +
          '</div></div>';
        container.appendChild(div);
      });
    });
  }

  function getWishlist() {
    try { return JSON.parse(localStorage.getItem('phantom_wishlist') || '[]'); } catch(e) { return []; }
  }
  function toggleWishlist(id) {
    var list = getWishlist();
    var idx = list.indexOf(id);
    if (idx === -1) list.push(id); else list.splice(idx, 1);
    try { localStorage.setItem('phantom_wishlist', JSON.stringify(list)); } catch(e) {}
    return list;
  }

  function initWishlistEvents() {
    document.addEventListener('click', function(e) {
      var trigger = e.target.closest('.phantom-wishlist-trigger');
      if (!trigger) return;
      e.preventDefault();
      var pid = parseInt(trigger.getAttribute('data-product-id'), 10);
      toggleWishlist(pid);
      var img = trigger.querySelector('img, i');
      if (img) { img.className = getWishlist().indexOf(pid) !== -1 ? 'fas fa-heart' : 'far fa-heart'; }
    });
  }

  function showQuickView(productId) {
    var overlay = document.querySelector('.phantom-qv-overlay') || (function() {
      var o = document.createElement('div');
      o.className = 'phantom-qv-overlay';
      o.innerHTML = '<div class="phantom-qv-modal"><button class="phantom-qv-close">&times;</button><div class="phantom-qv-body"><div class="phantom-qv-image"><img src="" alt=""></div><div class="phantom-qv-info"><h2></h2><div class="phantom-qv-price"></div><div class="phantom-qv-desc"></div><a href="#" class="phantom-qv-atc add-to-cart-trigger">Add to Cart</a></div></div></div>';
      document.body.appendChild(o);
      o.querySelector('.phantom-qv-close').addEventListener('click', function() { o.style.display = 'none'; });
      o.addEventListener('click', function(e) { if (e.target === o) o.style.display = 'none'; });
      return o;
    })();
    overlay.style.display = 'flex';
    var modal = overlay.querySelector('.phantom-qv-modal');
    var imgEl = modal.querySelector('.phantom-qv-image img');
    var titleEl = modal.querySelector('.phantom-qv-info h2');
    var priceEl = modal.querySelector('.phantom-qv-price');
    var descEl = modal.querySelector('.phantom-qv-desc');
    var atcEl = modal.querySelector('.phantom-qv-atc');
    fetchJSON('/products/' + productId).then(function(resp) {
      var p = resp && resp.product ? resp.product : (Array.isArray(resp) ? resp[0] : resp);
      if (!p) return;
      imgEl.src = p.image || '';
      titleEl.textContent = p.name || '';
      priceEl.innerHTML = p.price_html || '$' + (p.price || '0');
      descEl.innerHTML = (p.short_description || p.description || '').substring(0, 300);
      atcEl.setAttribute('data-product_id', p.id || '');
    }).catch(function(err) { console.error('[Phantom] Quick view error:', err); });
  }

  function initQuickViewEvents() {
    document.addEventListener('click', function(e) {
      var trigger = e.target.closest('.phantom-quickview-trigger');
      if (!trigger) return;
      e.preventDefault();
      var pid = parseInt(trigger.getAttribute('data-product-id'), 10);
      showQuickView(pid);
    });
  }

  function init360Viewer(canvas, images) {
    if (!canvas || !images || !images.length) return;
    var ctx = canvas.getContext('2d');
    var loaded = [];
    var current = 0;
    var isDown = false;
    var startX = 0;
    function preload(idx) {
      if (loaded[idx]) return;
      var img = new Image();
      img.onload = function() { loaded[idx] = img; if (idx === 0) drawFrame(0); };
      img.src = images[idx];
    }
    function drawFrame(idx) {
      var img = loaded[idx];
      if (!img) return;
      var scale = Math.min(canvas.width / img.width, canvas.height / img.height);
      var x = (canvas.width - img.width * scale) / 2;
      var y = (canvas.height - img.height * scale) / 2;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
    }
    images.forEach(function(_, i) { preload(i); });
    canvas.addEventListener('mousedown', function(e) { isDown = true; startX = e.clientX; });
    canvas.addEventListener('mousemove', function(e) {
      if (!isDown) return;
      var dx = e.clientX - startX;
      var dir = dx > 0 ? 1 : -1;
      current = (current + dir + images.length) % images.length;
      drawFrame(current);
      startX = e.clientX;
    });
    canvas.addEventListener('mouseup', function() { isDown = false; });
    canvas.addEventListener('mouseleave', function() { isDown = false; });
  }

  function initImageZoom() {
    var mainImg = document.querySelector('#myTabContent .tab-pane.active.show figure.auction-img img, #myTabContent .tab-pane:first-child figure.auction-img img');
    if (!mainImg) return;
    var wrapper = mainImg.parentElement;
    wrapper.style.overflow = 'hidden';
    wrapper.style.cursor = 'zoom-in';
    mainImg.addEventListener('mousemove', function(e) {
      var rect = wrapper.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      mainImg.style.transformOrigin = x + '% ' + y + '%';
      mainImg.style.transform = 'scale(2)';
    });
    mainImg.addEventListener('mouseleave', function() {
      mainImg.style.transform = 'scale(1)';
    });
  }

  function init() {
    if (document.__phantomThemeInited) return;
    document.__phantomThemeInited = true;

    fetchJSON('/page-data').then(function(data) {
      if (!data) return;
      if (data.settings) injectSettings(data.settings);
      if (data.menus) injectMenus(data.menus);
      if (data.products) injectProducts(data.products, data.settings);
      if (data.posts) injectPosts(data.posts);
    }).catch(function(err) {
      console.error('[PhantomTheme] Init error:', err);
    });

    initWishlistEvents();
    initQuickViewEvents();
    initImageZoom();

    var canvas = document.getElementById('product-360-canvas');
    if (canvas) {
      var productEl = canvas.closest('[data-phantom-product]');
      if (productEl) {
        var pid = productEl.getAttribute('data-product-id');
        if (pid) {
          fetchJSON('/products/' + pid).then(function(resp) {
            var p = resp && resp.product ? resp.product : (Array.isArray(resp) ? resp[0] : resp);
            if (p && p.images_360) init360Viewer(canvas, p.images_360);
          }).catch(function() {});
        }
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
