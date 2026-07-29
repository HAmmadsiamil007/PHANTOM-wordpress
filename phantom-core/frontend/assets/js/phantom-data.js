/* services/event-services.js */
(function(w) {
  'use strict';

  w.PhantomEvents = w.PhantomEvents || {
    handlers: {},
    settingHandlers: {},

    on: function(event, fn) {
      if (!this.handlers[event]) this.handlers[event] = [];
      this.handlers[event].push(fn);
      return this;
    },

    off: function(event, fn) {
      if (this.handlers[event]) {
        this.handlers[event] = this.handlers[event].filter(function(f) { return f !== fn; });
      }
      return this;
    },

    emit: function(event, data) {
      var list = this.handlers[event];
      if (list) {
        for (var i = 0; i < list.length; i++) {
          try { list[i](data); } catch (e) { console.warn('[PhantomEvents] handler error', e); }
        }
      }
    },

    onSettingChange: function(key, fn) {
      if (!this.settingHandlers[key]) this.settingHandlers[key] = [];
      this.settingHandlers[key].push(fn);
      return this;
    },

    offSettingChange: function(key, fn) {
      if (this.settingHandlers[key]) {
        this.settingHandlers[key] = this.settingHandlers[key].filter(function(f) { return f !== fn; });
      }
      return this;
    },

    emitSettingChange: function(key, value) {
      var list = this.settingHandlers[key] || [];
      for (var i = 0; i < list.length; i++) {
        try { list[i](value, key); } catch (e) { console.warn('[PhantomEvents] setting handler error', e); }
      }
    },

    consumeStore: function() {
      var events = w.PhantomData && w.PhantomData.events;
      if (!events || !events.length) return;
      for (var i = 0; i < events.length; i++) {
        this.emit(events[i].event, events[i].payload);
      }
    }
  };

  if (w.addEventListener) {
    w.addEventListener('DOMContentLoaded', function() {
      w.PhantomEvents.consumeStore();
    });
  }
})(window);


/* services/api-service.js */
(function(w) {
  'use strict';

  w.PhantomServices = w.PhantomServices || {};

  w.PhantomServices.Api = {
    baseUrl: (function() {
      var pd = w.PhantomData && w.PhantomData.rest_url
        ? w.PhantomData.rest_url.replace(/\/+$/, '') : '';
      return pd || '/index.php?rest_route=/phantom/v1';
    })(),

    cache: {},
    cacheTTL: 120000,

    get: function(path) {
      var self = this;
      var cacheKey = path;
      if (this.cache[cacheKey] && (Date.now() - this.cache[cacheKey].ts < this.cacheTTL)) {
        return Promise.resolve(this.cache[cacheKey].data);
      }
      return fetch(this.baseUrl + path, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
      }).then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      }).then(function(data) {
        self.cache[cacheKey] = { data: data, ts: Date.now() };
        return data;
      });
    },

    post: function(path, body) {
      var nonce = (w.PhantomData && w.PhantomData.api_nonce) || '';
      return fetch(this.baseUrl + path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Phantom-Nonce': nonce,
        },
        body: JSON.stringify(body),
      }).then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      });
    },

    invalidateCache: function(pattern) {
      var self = this;
      Object.keys(this.cache).forEach(function(key) {
        if (key.indexOf(pattern) !== -1) delete self.cache[key];
      });
    },

    getProducts: function(params) {
      var query = [];
      if (params) {
        if (params.per_page) query.push('per_page=' + params.per_page);
        if (params.page) query.push('page=' + params.page);
        if (params.category) query.push('category=' + encodeURIComponent(params.category));
        if (params.on_sale) query.push('on_sale=true');
      }
      var qs = query.length ? '?' + query.join('&') : '';
      return this.get('/products' + qs);
    },

    getPageData: function() {
      return this.get('/page-data');
    },

    getCart: function() {
      return this.get('/cart');
    },

    postContact: function(data) {
      return this.post('/contact', data);
    },

    fetchWithRetry: function(url, options) {
      options = options || {};
      var retries = options.retries || 1;
      function attempt(remaining) {
        return fetch(url, options)
          .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
          })
          .catch(function(err) {
            if (remaining > 0) return attempt(remaining - 1);
            throw err;
          });
      }
      return attempt(retries);
    },

    getNonce: function() {
      var data = w.PhantomData || {};
      return data.api_nonce || (w.wpApiSettings && w.wpApiSettings.nonce) || '';
    },

    setSetting: function(key, value) {
      var self = this;
      var body = { value: value };
      return this.fetchWithRetry(this.baseUrl + '/settings/' + encodeURIComponent(key), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-Phantom-Nonce': this.getNonce() },
        body: JSON.stringify(body),
        credentials: 'same-origin'
      }).then(function(resp) {
        w.PhantomEvents && w.PhantomEvents.emitSettingChange(key, value);
        return resp;
      });
    },

    saveChanges: function(changes) {
      var self = this;
      return this.fetchWithRetry(this.baseUrl + '/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Phantom-Nonce': this.getNonce() },
        body: JSON.stringify({ settings: changes }),
        credentials: 'same-origin'
      }).then(function(resp) {
        if (w.PhantomEvents) {
          Object.keys(changes).forEach(function(k) {
            w.PhantomEvents.emitSettingChange(k, changes[k]);
          });
        }
        return resp;
      });
    }
  };
})(window);


/* services/cart-service.js */
(function(w) {
  'use strict';

  w.PhantomServices = w.PhantomServices || {};

  w.PhantomServices.Cart = {
    init: function() {
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('.phantom-add-to-cart, .wc-add-to-cart-btn');
        if (btn) {
          e.preventDefault();
          var productId = btn.getAttribute('data-product_id') || btn.getAttribute('data-product-id');
          if (!productId) return;

          // Check if the button is inside a form (product detail page)
          var form = btn.closest('form');
          var quantity = 1;
          var variationData = null;

          if (form) {
            // Read quantity
            var qtyEl = form.querySelector('.pd-qty-value');
            if (qtyEl) quantity = parseInt(qtyEl.textContent) || 1;

            // Read variation selects + hidden inputs (variable products)
            var selects = form.querySelectorAll('.pd-variation-select');
            var hiddenAttrs = form.querySelectorAll('input[type="hidden"][name^="attribute_"]');
            if (selects.length || hiddenAttrs.length) {
              variationData = {};
              selects.forEach(function(sel) {
                if (sel.value) variationData[sel.name] = sel.value;
              });
              hiddenAttrs.forEach(function(inp) {
                variationData[inp.name] = inp.value;
              });
              // Variable product requires variation_id. Try to find it from data attributes
              var variationId = form.getAttribute('data-variation_id') || btn.getAttribute('data-variation_id');
              if (variationId) variationData.variation_id = parseInt(variationId);
            }
          }

          this.add(productId, quantity, variationData);
        }
      }.bind(this));

      // Handle variation select changes — update variation_id and price
      document.addEventListener('change', function(e) {
        var sel = e.target.closest('.pd-variation-select');
        if (!sel) return;
        var form = sel.closest('.pd-variations-form');
        if (!form) return;

        var allSelected = true;
        var selects = form.querySelectorAll('.pd-variation-select');
        var hiddenAttrs = form.querySelectorAll('input[type="hidden"][name^="attribute_"]');
        var data = {};
        selects.forEach(function(s) {
          if (!s.value) allSelected = false;
          else data[s.name] = s.value;
        });
        hiddenAttrs.forEach(function(inp) {
          data[inp.name] = inp.value;
        });

        if (allSelected) {
          var productId = form.getAttribute('data-product_id');
          if (productId && w.PhantomServices.Api) {
            w.PhantomServices.Api.get('/products/' + productId).then(function(product) {
              if (product && product.variations) {
                // Find matching variation
                var matched = null;
                product.variations.forEach(function(v) {
                  if (!v.attributes) return;
                  var match = true;
                  // Check each attribute matches the selected value
                  Object.keys(data).forEach(function(key) {
                    var attrKey = key.replace('attribute_', '');
                    var attrVal = data[key];
                    var vAttr = v.attributes[attrKey] || v.attributes['pa_' + attrKey] || v.attributes['attribute_' + attrKey];
                    if (vAttr !== undefined && String(vAttr).toLowerCase() !== String(attrVal).toLowerCase()) {
                      match = false;
                    }
                  });
                  if (match) matched = v;
                });

                if (matched) {
                  form.setAttribute('data-variation_id', matched.id);
                  // Update price display
                  var priceEl = form.closest('[data-component="product-detail"]') || document;
                  var priceTarget = priceEl.querySelector('.product-price-content, .product-price, .pd-price');
                  if (priceTarget && matched.price_html) {
                    priceTarget.innerHTML = matched.price_html;
                  }
                }
              }
            });
          }
        }
      });
    },

    add: function(productId, quantity, variationData) {
      quantity = quantity || 1;
      var body = { product_id: parseInt(productId), quantity: parseInt(quantity) };
      if (variationData) {
        body.variation = variationData;
        if (variationData.variation_id) {
          body.variation_id = variationData.variation_id;
          delete variationData.variation_id;
        }
      }

      return w.PhantomServices.Api.post('/cart/add', body).then(function(resp) {
        if (resp && !resp.error) {
          w.PhantomServices.Api.invalidateCache('/cart');
          w.PhantomServices.Api.getCart().then(function(cart) {
            w.PhantomEvents && w.PhantomEvents.emit('cart:updated', cart);
          });
          w.PhantomEvents && w.PhantomEvents.emit('cart:added', { product_id: productId });
        }
        return resp;
      }).catch(function(err) {
        console.error('[Phantom Cart] Add error:', err);
      });
    },

    remove: function(cartItemKey) {
      return w.PhantomServices.Api.post('/cart/remove', { cart_item_key: cartItemKey }).then(function(resp) {
        w.PhantomServices.Api.invalidateCache('/cart');
        w.PhantomEvents && w.PhantomEvents.emit('cart:updated', resp);
        return resp;
      });
    },

    updateQuantity: function(cartItemKey, quantity) {
      return w.PhantomServices.Api.post('/cart/update', { cart_item_key: cartItemKey, quantity: parseInt(quantity) }).then(function(resp) {
        w.PhantomServices.Api.invalidateCache('/cart');
        w.PhantomEvents && w.PhantomEvents.emit('cart:updated', resp);
        return resp;
      });
    },

    getCount: function() {
      return w.PhantomServices.Api.getCart().then(function(cart) {
        return cart ? cart.cart_contents_count || 0 : 0;
      });
    },

    getTotal: function() {
      return w.PhantomServices.Api.getCart().then(function(cart) {
        return cart ? cart.total || '' : '';
      });
    }
  };

  w.PhantomServices.Cart.init();
})(window);


/* services/auth-service.js */
(function(w) {
  'use strict';

  w.PhantomServices = w.PhantomServices || {};

  w.PhantomServices.Auth = {
    login: function(username, password) {
      return w.PhantomServices.Api.post('/auth/login', {
        username: username,
        password: password,
      }).then(function(resp) {
        if (resp && resp.success) {
          w.PhantomEvents && w.PhantomEvents.emit('auth:login', resp);
        }
        return resp;
      });
    },

    register: function(data) {
      return w.PhantomServices.Api.post('/auth/register', data).then(function(resp) {
        if (resp && resp.success) {
          w.PhantomEvents && w.PhantomEvents.emit('auth:register', resp);
        }
        return resp;
      });
    },

    logout: function() {
      return w.PhantomServices.Api.post('/auth/logout', {}).then(function(resp) {
        w.PhantomEvents && w.PhantomEvents.emit('auth:logout', resp);
        return resp;
      });
    },

    resetPassword: function(email) {
      return w.PhantomServices.Api.post('/auth/reset-password', { user_email: email });
    }
  };
})(window);


/* adapters/product-adapter.js */
(function(w) {
  'use strict';

  w.PhantomAdapters = w.PhantomAdapters || {};

  w.PhantomAdapters.ProductAdapter = {
    normalize: function(raw) {
      var p = raw || {};
      var gallery = p.images ? p.images.map(function(i) { return i.src || i; }) : [];
      if (p.image && gallery.indexOf(p.image) === -1) gallery.unshift(p.image);

      return {
        id: p.id || 0,
        name: p.name || '',
        slug: p.slug || '',
        url: p.permalink || '/?product_id=' + (p.id || ''),
        image: p.image || '',
        image_alt: p.name || '',
        gallery: gallery,
        price: p.price_html || '',
        regular_price: p.regular_price || p.price || '',
        sale_price: p.sale_price || '',
        on_sale: !!p.on_sale,
        is_featured: !!p.is_featured,
        in_stock: p.stock_status === 'instock',
        rating: parseFloat(p.average_rating) || 0,
        reviews_count: parseInt(p.review_count) || 0,
        sku: p.sku || '',
        categories: p.categories || [],
        tags: p.tags || [],
        type: p.type || 'simple',
        short_description: p.short_description || '',
        description: p.description || '',
        variations: p.variations || [],
        variation_attributes: p.attributes || [],
      };
    },

    normalizeCollection: function(rawArray) {
      var self = this;
      return (rawArray || []).map(function(item) { return self.normalize(item); });
    }
  };
})(window);


/* adapters/category-adapter.js */
(function(w) {
  'use strict';
  w.PhantomAdapters = w.PhantomAdapters || {};

  w.PhantomAdapters.CategoryAdapter = {
    normalize: function(raw) {
      var cat = raw || {};
      return {
        id: cat.id || 0,
        name: cat.name || '',
        slug: cat.slug || '',
        url: cat.url || '/?product_cat=' + encodeURIComponent(cat.slug || ''),
        image: cat.image || '',
        count: cat.count || 0,
        description: cat.description || '',
      };
    },
    normalizeCollection: function(rawArray) {
      var self = this;
      return (rawArray || []).map(function(item) { return self.normalize(item); });
    }
  };
})(window);


/* renderer/component-renderer.js */
(function(w) {
  'use strict';

  w.PhantomRenderer = w.PhantomRenderer || {};

  w.PhantomRenderer.ComponentRenderer = {
    renderTemplate: function(tpl, data) {
      return tpl.replace(/\{\{(\w+)\}\}/g, function(m, k) {
        return data[k] !== undefined ? data[k] : m;
      });
    },

    escapeHtml: function(str) {
      if (!str) return '';
      var d = document.createElement('div');
      d.textContent = str;
      return d.innerHTML;
    },

    sanitizeUrl: function(url) {
      if (!url) return '#';
      url = url.trim();
      if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) return url;
      return '#';
    },

    getCurrencySymbol: function() {
      return w.PhantomData && w.PhantomData.currency_symbol
        ? w.PhantomData.currency_symbol : '$';
    },

    createElement: function(html) {
      var d = document.createElement('div');
      d.innerHTML = html;
      return d.firstElementChild || d.firstChild;
    }
  };
})(window);


/* renderer/product-card.js */
(function(w) {
  'use strict';
  var R = w.PhantomRenderer = w.PhantomRenderer || {};
  var CR = R.ComponentRenderer;

  var DEFAULT_TPL =
    '<div class="product-card" data-tilt data-reveal-item>' +
      '<div class="product-image" data-image-zoom>' +
        '{{BADGE}}' +
        '<a href="{{URL}}"><img loading="lazy" src="{{IMAGE}}" alt="{{NAME}}"></a>' +
        '{{ACTIONS}}' +
      '</div>' +
      '<div class="product-info">' +
        '{{RATING}}' +
        '{{CATEGORIES}}' +
        '<div class="product-price-row">' +
          '<span class="product-price">{{PRICE}}</span>' +
          '{{ATC_BUTTON}}' +
        '</div>' +
      '</div>' +
    '</div>';

  R.ProductCard = {
    template: DEFAULT_TPL,

    setTemplate: function(tpl) {
      this.template = tpl;
    },

    render: function(data, settings) {
      var d = data;
      var catMode = settings ? !!+settings.shop_catalog_mode : false;
      var showWishlist = settings ? !!+settings.shop_wishlist_enable : false;
      var showQuickView = settings ? !!+settings.card_quick_view : false;

      var badge = '';
      if (d.on_sale) {
        badge = '<span class="product-badge badge-sale">Sale</span>';
      } else if (d.is_featured) {
        badge = '<span class="product-badge badge-new">New</span>';
      }

      var stars = '';
      if (d.rating > 0) {
        var full = Math.floor(d.rating);
        for (var i = 0; i < 5; i++) {
          stars += i < full ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
        }
      }
      var rating = d.rating > 0
        ? '<div class="product-rating">' + stars + '<span>(' + (d.reviews_count || 0) + ')</span></div>'
        : '';

      var cats = '';
      if (d.categories && d.categories.length) {
        var names = d.categories.slice(0, 2).map(function(c) { return c.name; });
        cats = '<div class="product-tagline">' + CR.escapeHtml(names.join(', ')) + '</div>';
      }

      var price = d.price || d.regular_price;
      if (d.on_sale && d.sale_price) {
        price = '<span class="price-sale">' + d.sale_price + '</span><span class="price-original">' + d.regular_price + '</span>';
      }

      var actions = '';
      if (showWishlist || showQuickView) {
        actions = '<div class="product-actions">';
        if (showWishlist) {
          actions += '<button class="product-action-btn phantom-wishlist-trigger" data-product-id="' + d.id + '" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>';
        }
        if (showQuickView) {
          actions += '<button class="product-action-btn phantom-quickview-trigger" data-product-id="' + d.id + '" aria-label="Quick view"><i class="far fa-eye"></i></button>';
        }
        actions += '</div>';
      }

      var atc = catMode ? '' : '<a href="' + d.url + '" class="btn btn-sm btn-primary phantom-add-to-cart" data-product_id="' + d.id + '" data-magnetic="0.12">Add to Cart</a>';

      return CR.renderTemplate(this.template, {
        BADGE: badge,
        URL: CR.sanitizeUrl(d.url),
        IMAGE: d.image,
        NAME: CR.escapeHtml(d.name),
        ACTIONS: actions,
        RATING: rating,
        CATEGORIES: cats,
        PRICE: price,
        ATC_BUTTON: atc,
      });
    },

    renderAll: function(products, settings) {
      var self = this;
      return products.map(function(p) { return self.render(p, settings); }).join('');
    }
  };
})(window);


/* renderer/category-card.js */
(function(w) {
  'use strict';
  var R = w.PhantomRenderer = w.PhantomRenderer || {};
  var CR = R.ComponentRenderer;

  var DEFAULT_TPL =
    '<a href="{{URL}}" class="category-card" data-tilt data-reveal-item>' +
      '<div class="category-card-bg">' +
        '<img loading="lazy" src="{{IMAGE}}" alt="{{NAME}}">' +
        '<div class="category-card-overlay"></div>' +
      '</div>' +
      '<div class="category-card-content">' +
        '<span class="category-count">{{COUNT}}</span>' +
        '<h3 class="category-name">{{NAME}}</h3>' +
        '<span class="category-cta">{{CTA}} <i class="fas fa-arrow-right"></i></span>' +
      '</div>' +
    '</a>';

  R.CategoryCard = {
    template: DEFAULT_TPL,

    setTemplate: function(tpl) {
      this.template = tpl;
    },

    render: function(data) {
      return CR.renderTemplate(this.template, {
        URL: CR.sanitizeUrl(data.url),
        IMAGE: data.image || '',
        NAME: CR.escapeHtml(data.name),
        COUNT: (data.count || 0) + ' items',
        CTA: 'Shop ' + CR.escapeHtml(data.name),
      });
    },

    renderAll: function(categories) {
      var self = this;
      return categories.map(function(c) { return self.render(c); }).join('');
    }
  };
})(window);


/* renderer/hero.js */
(function(w) {
  'use strict';
  var R = w.PhantomRenderer = w.PhantomRenderer || {};
  var CR = R.ComponentRenderer;

  R.HeroRenderer = {
    render: function(data) {
      var image = data.image || '';
      var imageTablet = data.enable_responsive && data.image_tablet ? data.image_tablet : image;
      var imageMobile = data.enable_responsive && data.image_mobile ? data.image_mobile : image;

      var picture = '';
      if (data.enable_responsive) {
        picture = '<picture>';
        if (imageTablet !== image) {
          picture += '<source media="(max-width:' + (data.tablet_breakpoint || 1024) + 'px)" srcset="' + CR.sanitizeUrl(imageTablet) + '">';
        }
        if (imageMobile !== image) {
          picture += '<source media="(max-width:' + (data.mobile_breakpoint || 768) + 'px)" srcset="' + CR.sanitizeUrl(imageMobile) + '">';
        }
        picture += '<img src="' + CR.sanitizeUrl(image) + '" alt="' + CR.escapeHtml(data.title) + '" class="hero-image" loading="' + (data.loading || 'lazy') + '">';
        picture += '</picture>';
      }

      var html = '<section class="hero-section" style="--hero-overlay-opacity:' + (data.overlay_opacity || 0.3) + '">';
      if (picture) {
        html += picture;
      } else if (image) {
        html += '<img src="' + CR.sanitizeUrl(image) + '" alt="' + CR.escapeHtml(data.title) + '" class="hero-image" loading="' + (data.loading || 'lazy') + '">';
      }
      html += '<div class="hero-content">';
      html += '<h1 class="hero-title">' + CR.escapeHtml(data.title) + '</h1>';
      if (data.subtitle) html += '<p class="hero-subtitle">' + CR.escapeHtml(data.subtitle) + '</p>';
      if (data.description) html += '<p class="hero-description">' + CR.escapeHtml(data.description) + '</p>';
      html += '<a href="' + CR.sanitizeUrl(data.btn_url) + '" class="btn btn-primary hero-cta">' + CR.escapeHtml(data.btn_text) + '</a>';
      html += '</div></section>';

      return html;
    }
  };
})(window);


/* phantom-core.js */
(function(w) {
  'use strict';

  // Homepage detection — skip dynamic injection to preserve static AETHER design
  function isHomepage() {
    var path = w.location.pathname.replace(/\/+$/, '');
    return path === '' || path === '/' || path === '/index.html' || path === '/index.php';
  }

  w.PhantomCore = {
    adapters: null,
    renderer: null,
    services: null,
    initialized: false,

    init: function() {
      if (this.initialized) return;
      this.adapters = w.PhantomAdapters || {};
      this.renderer = w.PhantomRenderer || {};
      this.services = w.PhantomServices || {};
      this.initialized = true;

      document.addEventListener('DOMContentLoaded', this.onReady.bind(this));
    },

    onReady: function() {
      // Skip dynamic injection on homepage — static AETHER design is already complete
      if (isHomepage()) return;

      var services = this.services;
      var adapters = this.adapters;
      var renderer = this.renderer;

      services.Api.getPageData().then(function(data) {
        if (!data) return;
        window.PhantomInjector.injectSettings(data.settings);
        window.PhantomInjector.injectMenus(data.menus);
        window.PhantomInjector.injectProducts(data.products, data.settings);
      }).catch(function(err) {
        console.error('[PhantomCore] Init error:', err);
      });
    },
  };

  w.PhantomCore.init();

  // Initialize PhantomInjector with data from PhantomData (skip on homepage)
  (function () {
    if (!window.PhantomInjector) return;
    if (isHomepage()) return;

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

  // Backward-compatible PhantomBridge shim
  (function() {
    if (w.PhantomBridge) return;
    w.PhantomBridge = {
      init: function() { return this; },
      getSetting: function(key) {
        return w.PhantomData ? w.PhantomData[key] : undefined;
      },
      setSetting: function(key, value) {
        return w.PhantomServices && w.PhantomServices.Api
          ? w.PhantomServices.Api.setSetting(key, value)
          : Promise.reject(new Error('API service unavailable'));
      },
      saveChanges: function(changes) {
        return w.PhantomServices && w.PhantomServices.Api
          ? w.PhantomServices.Api.saveChanges(changes)
          : Promise.reject(new Error('API service unavailable'));
      },
      onSettingChange: function(key, fn) {
        w.PhantomEvents && w.PhantomEvents.onSettingChange(key, fn);
        return this;
      },
      offSettingChange: function(key, fn) {
        w.PhantomEvents && w.PhantomEvents.offSettingChange(key, fn);
        return this;
      },
      highlightElement: function(selector) {
        var el = document.querySelector(selector);
        if (el) {
          el.classList.add('phantom-highlight');
          setTimeout(function() { el.classList.remove('phantom-highlight'); }, 2000);
        }
      },
      openEditor: function(key) {
        var url = (w.wpAdminUrl || '/wp-admin/') + 'customize.php?autofocus[control]=phantom_' + encodeURIComponent(key);
        window.open(url, '_blank');
      },
      getCssVars: function() {
        return {};
      }
    };
  })();
})(window);


