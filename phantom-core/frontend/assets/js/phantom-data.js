/**
 * Phantom Data Bridge — Core Data Injection System
 * Fetches data from WordPress REST API and injects into HTML templates
 * Works with Phantom Core plugin (34 REST endpoints, 555 settings)
 */
(function() {
  'use strict';

  // === Configuration ===
  const CONFIG = window.phantomData || {};
  const REST_URL = CONFIG.rest_url || '/wp-json/';
  const NONCE = CONFIG.nonce || '';
  const PLUGIN_URL = CONFIG.plugin_url || '';
  const SITE_NAME = CONFIG.site_name || 'AETHER';
  const IS_LOGGED_IN = CONFIG.is_logged_in || false;

  let cachedData = null;
  let cacheExpiry = 0;
  const CACHE_DURATION = 3600000; // 1 hour

  // === Security Helpers ===
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function sanitizeUrl(url) {
    if (!url) return '#';
    const allowed = ['http:', 'https:', 'mailto:', 'tel:'];
    try {
      const parsed = new URL(url, window.location.origin);
      if (allowed.includes(parsed.protocol) || url.startsWith('/')) {
        return url;
      }
    } catch (e) {
      if (url.startsWith('/') || url.startsWith('#')) return url;
    }
    return '#';
  }

  function resolveUrl(path) {
    if (!path) return '';
    if (path.startsWith('http') || path.startsWith('data:')) return path;
    if (PLUGIN_URL && !path.startsWith('/')) {
      return PLUGIN_URL + '/assets/' + path;
    }
    return path;
  }

  // === Data Fetching ===
  async function fetchPageData() {
    if (cachedData && Date.now() < cacheExpiry) {
      return cachedData;
    }
    try {
      const response = await fetch(REST_URL + 'phantom/v1/page-data', {
        headers: {
          'X-WP-Nonce': NONCE,
          'Content-Type': 'application/json'
        }
      });
      if (!response.ok) throw new Error('API error: ' + response.status);
      cachedData = await response.json();
      cacheExpiry = Date.now() + CACHE_DURATION;
      return cachedData;
    } catch (err) {
      console.warn('[Phantom] API fetch failed, using fallback:', err.message);
      return null;
    }
  }

  // === Injection Functions ===
  function injectSettings(data) {
    if (!data || !data.settings) return;
    const settings = data.settings;
    document.querySelectorAll('[data-phantom]').forEach(el => {
      const key = el.getAttribute('data-phantom');
      if (settings[key] !== undefined && settings[key] !== null) {
        const value = settings[key];
        if (el.tagName === 'IMG' || el.tagName === 'SOURCE') {
          el.setAttribute('src', resolveUrl(value));
        } else if (el.tagName === 'A') {
          el.setAttribute('href', sanitizeUrl(value));
        } else {
          el.textContent = value;
        }
      }
    });
    document.querySelectorAll('[data-phantom-bg]').forEach(el => {
      const key = el.getAttribute('data-phantom-bg');
      if (settings[key]) {
        el.style.backgroundImage = 'url(' + resolveUrl(settings[key]) + ')';
      }
    });
    document.querySelectorAll('[data-phantom-alt]').forEach(el => {
      const key = el.getAttribute('data-phantom-alt');
      if (settings[key]) {
        el.setAttribute('alt', settings[key]);
      }
    });
  }

  function injectMenus(data) {
    if (!data || !data.menus) return;
    document.querySelectorAll('[data-phantom-menu]').forEach(nav => {
      const location = nav.getAttribute('data-phantom-menu');
      const menuData = data.menus[location];
      if (!menuData || menuData.length === 0) return;
      const html = buildMenuHTML(menuData);
      if (nav.tagName === 'NAV' || nav.tagName === 'UL') {
        nav.innerHTML = html;
      } else {
        nav.innerHTML = html;
      }
    });
  }

  function buildMenuHTML(items) {
    if (!items || !Array.isArray(items)) return '';
    let html = '<ul class="nav-menu">';
    items.forEach(item => {
      const hasChildren = item.children && item.children.length > 0;
      html += '<li class="' + (hasChildren ? 'menu-item-has-children' : '') + '">';
      html += '<a href="' + sanitizeUrl(item.url || '#') + '">' + escapeHtml(item.title) + '</a>';
      if (hasChildren) {
        html += buildMenuHTML(item.children);
      }
      html += '</li>';
    });
    html += '</ul>';
    return html;
  }

  function injectProducts(data) {
    if (!data || !data.products) return;
    document.querySelectorAll('[data-phantom-products]').forEach(container => {
      const type = container.getAttribute('data-phantom-products');
      const count = parseInt(container.getAttribute('data-count')) || 4;
      let products = [];
      if (type === 'featured' && data.products.featured) {
        products = data.products.featured.slice(0, count);
      } else if (type === 'shop') {
        products = data.products.all ? data.products.all.slice(0, count) : [];
      }
      if (!products || products.length === 0) return;
      container.innerHTML = products.map(p => buildProductCard(p)).join('');
    });
  }

  function buildProductCard(product) {
    if (!product) return '';
    const img = product.image ? '<img src="' + resolveUrl(product.image) + '" alt="' + escapeHtml(product.name) + '" loading="lazy">' : '';
    const badge = product.badge ? '<span class="product-badge">' + escapeHtml(product.badge) + '</span>' : '';
    const price = product.price ? '<span class="product-price">' + escapeHtml(product.price) + '</span>' : '';
    const rating = product.rating ? '<div class="product-rating">' + '★'.repeat(Math.floor(product.rating)) + ' (' + product.rating_count + ')</div>' : '';
    return '<div class="product-card" data-product-id="' + (product.id || '') + '">' +
      '<div class="product-image" data-image-zoom>' + img + badge +
      '<div class="product-actions">' +
      '<button class="product-action-btn add-to-cart-trigger" data-product-id="' + (product.id || '') + '" aria-label="Add to wishlist"><i class="fas fa-heart"></i></button>' +
      '<button class="product-action-btn" aria-label="Quick view"><i class="fas fa-eye"></i></button>' +
      '</div></div>' +
      '<div class="product-info">' + rating +
      '<h3 class="product-name">' + escapeHtml(product.name) + '</h3>' +
      '<p class="product-tagline">' + escapeHtml(product.tagline || '') + '</p>' +
      '<div class="product-price-row">' + price +
      '<a href="' + sanitizeUrl(product.url || 'product-detail.html') + '" class="btn btn-sm btn-primary add-to-cart-trigger" data-product-id="' + (product.id || '') + '">Add to Cart</a>' +
      '</div></div></div>';
  }

  function injectPosts(data) {
    if (!data || !data.posts) return;
    document.querySelectorAll('[data-phantom-posts]').forEach(container => {
      const type = container.getAttribute('data-phantom-posts');
      const posts = data.posts.slice(0, parseInt(container.getAttribute('data-count')) || 6);
      if (!posts || posts.length === 0) return;
      container.innerHTML = posts.map(p => buildPostCard(p)).join('');
    });
  }

  function buildPostCard(post) {
    if (!post) return '';
    const img = post.image ? '<img src="' + resolveUrl(post.image) + '" alt="' + escapeHtml(post.title) + '" loading="lazy">' : '';
    return '<a href="' + sanitizeUrl(post.url || 'single-blog.html') + '" class="blog-card" data-tilt data-reveal-item>' +
      '<div class="blog-card-image" data-image-zoom>' + img +
      '<span class="blog-category">' + escapeHtml(post.category || 'Blog') + '</span></div>' +
      '<div class="blog-card-content">' +
      '<span class="blog-date">' + escapeHtml(post.date || '') + '</span>' +
      '<h3 class="blog-card-title">' + escapeHtml(post.title) + '</h3>' +
      '<p class="blog-card-excerpt">' + escapeHtml(post.excerpt || '') + '</p>' +
      '</div></a>';
  }

  function injectCart(data) {
    if (!data || !data.cart) return;
    const cart = data.cart;
    document.querySelectorAll('.cart-count').forEach(el => {
      el.textContent = cart.item_count || 0;
    });
    document.querySelectorAll('.cart-total').forEach(el => {
      el.textContent = cart.total || '$0.00';
    });
    const cartInfo = document.querySelector('.shopping-cart-info');
    if (cartInfo) {
      cartInfo.innerHTML = '<span class="cart-item-count">' + (cart.item_count || 0) + ' items</span>' +
        '<span class="cart-total-price">' + escapeHtml(cart.total || '$0.00') + '</span>';
    }
  }

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
