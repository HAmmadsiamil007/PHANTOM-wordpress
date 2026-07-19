/**
 * Phantom Core Data Bridge v2.0
 * Fetches data from Phantom Core REST API and injects into DOM.
 * Built for Layout-02-Kids-Collection frontend.
 */
(function () {
  'use strict';

  const apiBase = window.phantomData && window.phantomData.rest_url ? window.phantomData.rest_url.replace(/\/+$/, '') : (window.wpApiSettings && window.wpApiSettings.root ? window.wpApiSettings.root.replace(/\/+$/, '') + '/phantom/v1' : '/index.php?rest_route=/phantom/v1');
  const cache = {};
  const cacheTTL = 120000;
  var _settings = {};

  function fetchJSON(path, options) {
    options = options || {};
    var method = options.method || 'GET';
    var body = options.body;

    if (method === 'GET' && cache[path] && (Date.now() - cache[path].ts < cacheTTL)) {
      return Promise.resolve(cache[path].data);
    }

    var timeout = options.timeout || 10000;
    var controller = new AbortController();
    var timer = setTimeout(function () { controller.abort(); }, timeout);

    var qIdx = path.indexOf('?');
    var url;
    if (qIdx === -1) {
      url = apiBase + path;
    } else {
      var baseHasQuery = apiBase.indexOf('?') !== -1;
      url = apiBase + path.substring(0, qIdx) + (baseHasQuery ? '&' : '?') + path.substring(qIdx + 1);
    }

    var fetchOpts = {
      method: method,
      signal: controller.signal,
      credentials: 'same-origin'
    };

    if (method !== 'GET') {
      var apiNonce = (window.phantomData && window.phantomData.api_nonce) || (window.PhantomData && window.PhantomData.api_nonce) || '';
      fetchOpts.headers = {
        'Content-Type': 'application/json',
        'X-Phantom-Nonce': apiNonce
      };
      if (body) {
        fetchOpts.body = JSON.stringify(body);
      }
    }

    return fetch(url, fetchOpts).then(function (r) {
      clearTimeout(timer);
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    }).then(function (data) {
      if (method === 'GET') {
        cache[path] = { data: data, ts: Date.now() };
      }
      return data;
    }).catch(function (err) {
      clearTimeout(timer);
      throw err;
    });
  }

  // ─── SETTINGS ────────────────────────────────────────────

  function injectSettings(settings) {
    if (!settings) return;

    // data-phantom="key" — replaces textContent (or src for IMG)
    document.querySelectorAll('[data-phantom]').forEach(function (el) {
      const key = el.getAttribute('data-phantom');
      if (settings[key] === undefined || settings[key] === null) return;
      let val = String(settings[key]);
      if (el.tagName === 'IMG' || el.tagName === 'SOURCE') {
        // Prepend assets/images/ for relative paths that don't have it
        if (val.indexOf('/') !== 0 && val.indexOf('http') !== 0 && val.indexOf('assets/') !== 0) {
          val = 'assets/images/' + val;
        }
        el.setAttribute('src', val);
      } else if (el.tagName === 'A' && el.hasAttribute('href')) {
        el.setAttribute('href', sanitizeUrl(val));
      } else {
        el.innerHTML = escapeHtml(val).replace(/\n/g, '<br>');
      }
    });

    // data-phantom-bg="key" — sets background-image
    document.querySelectorAll('[data-phantom-bg]').forEach(function (el) {
      const key = el.getAttribute('data-phantom-bg');
      if (settings[key]) el.style.backgroundImage = 'url("' + settings[key].replace(/[^a-zA-Z0-9\-._~:\/?#@!$&'(*+,;=]/g, '') + '")';
    });
  }

  // ─── MENUS ───────────────────────────────────────────────

  function stripHtml(str) {
    if (!str) return '';
    return str.replace(/<[^>]*>/g, '');
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function sanitizeUrl(url) {
    if (!url) return '#';
    url = url.trim();
    if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) return url;
    return '#';
  }

  function buildMenuHTML(items) {
    const frag = document.createDocumentFragment();
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const hasChildren = item.children && item.children.length > 0;
      const li = document.createElement('li');
      li.className = 'nav-item' + (hasChildren ? ' dropdown' : '');
      if (hasChildren) {
        const a = document.createElement('a');
        a.className = 'nav-link dropdown-toggle dropdown-color navbar-text-color';
        a.href = sanitizeUrl(item.url);
        a.setAttribute('role', 'button');
        a.setAttribute('data-toggle', 'dropdown');
        a.setAttribute('aria-haspopup', 'true');
        a.setAttribute('aria-expanded', 'false');
        a.textContent = item.title;
        li.appendChild(a);
        const div = document.createElement('div');
        div.className = 'dropdown-menu drop-down-content';
        const ul = document.createElement('ul');
        ul.className = 'list-unstyled drop-down-pages';
        for (let j = 0; j < item.children.length; j++) {
          const child = item.children[j];
          const childLi = document.createElement('li');
          childLi.className = 'nav-item';
          const childA = document.createElement('a');
          childA.className = 'dropdown-item nav-link';
          childA.href = sanitizeUrl(child.url);
          childA.textContent = child.title;
          childLi.appendChild(childA);
          ul.appendChild(childLi);
        }
        div.appendChild(ul);
        li.appendChild(div);
      } else {
        const a = document.createElement('a');
        a.className = 'nav-link';
        a.href = sanitizeUrl(item.url);
        a.textContent = item.title;
        li.appendChild(a);
      }
      frag.appendChild(li);
    }
    const wrapper = document.createElement('div');
    wrapper.appendChild(frag);
    return wrapper.innerHTML;
  }

  function injectMenus(menus) {
    if (!menus) return;
    document.querySelectorAll('[data-phantom-menu]').forEach(function (el) {
      const location = el.getAttribute('data-phantom-menu');
      const menu = menus[location];
      const items = (menu && menu.items) || [];
      if (!items.length) return;
      el.innerHTML = buildMenuHTML(items);
      const path = window.location.pathname;
      const origin = window.location.origin;
      el.querySelectorAll('a.nav-link').forEach(function (a) {
        const href = a.getAttribute('href');
        // Match by pathname (relative) or full URL (absolute)
        if (href === path || href === origin + path) {
          a.classList.add('active');
        }
      });
      el.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
        if (typeof $ !== 'undefined' && $.fn.dropdown) {
          $(toggle).dropdown();
        }
      });
    });
  }

  // ─── PRODUCTS ────────────────────────────────────────────

  function buildProductCard(p, showSaleBadge, saleBadgeText, settings) {
    const imgSrc = p.image || '';
    const priceHtml = p.price_html || '$' + (p.price || '0');
    const detailUrl = '/product/?product_id=' + (p.id || '');
    const isSale = p.on_sale;
    const isFeatured = p.is_featured;
    const salePrice = p.sale_price || '';
    const regPrice = p.regular_price || p.price || '';
    var catalogMode = settings ? !!+settings.shop_catalog_mode : false;
    var wishlistEnabled = settings ? !!+settings.shop_wishlist_enable : false;
    var quickViewEnabled = settings ? !!+settings.card_quick_view : false;

    const outer = document.createElement('div');
    outer.className = 'col-xl-4 col-lg-6 col-md-6 col-sm-6 d-flex';
    const sellerBox = document.createElement('div');
    sellerBox.className = 'seller-box w-100';
    const imgBox = document.createElement('div');
    imgBox.className = 'seller_image_box position-relative';
    if (isSale && showSaleBadge) {
      const saleTag = document.createElement('span');
      saleTag.className = 'd-inline-block position-absolute sale-tag background-primary text-white';
      saleTag.textContent = saleBadgeText;
      imgBox.appendChild(saleTag);
    }
    if (isFeatured) {
      const featTag = document.createElement('span');
      featTag.className = 'd-inline-block position-absolute featured-tag';
      featTag.textContent = 'Featured';
      featTag.style.top = isSale ? '48px' : '20px';
      imgBox.appendChild(featTag);
    }
    const figure = document.createElement('figure');
    figure.className = 'mb-0';
    const figLink = document.createElement('a');
    figLink.href = detailUrl;
    const prodImg = document.createElement('img');
    prodImg.src = imgSrc;
    prodImg.alt = p.name || '';
    prodImg.className = 'img-fluid';
    prodImg.loading = 'lazy';
    figLink.appendChild(prodImg);
    figure.appendChild(figLink);
    imgBox.appendChild(figure);
    const ul = document.createElement('ul');
    ul.className = 'list-unstyled mb-0';
    // Cart icon — hidden in catalog mode
    if (!catalogMode) {
      const cartLi = document.createElement('li');
      cartLi.className = 'icon';
      const cartLink = document.createElement('a');
      cartLink.href = detailUrl;
      cartLink.className = 'add-to-cart-trigger';
      cartLink.setAttribute('data-product_id', p.id || '');
      cartLink.setAttribute('data-product_sku', p.sku || '');
      const cartImg = document.createElement('img');
      cartImg.src = resolveUrl('assets/images/feature-cart.png');
      cartImg.alt = 'cart';
      cartImg.className = 'img-fluid';
      cartLink.appendChild(cartImg);
      cartLi.appendChild(cartLink);
      ul.appendChild(cartLi);
    }
    // Wishlist icon — only when setting enabled
    if (wishlistEnabled) {
      const heartLi = document.createElement('li');
      heartLi.className = 'icon phantom-wishlist-icon';
      const heartLink = document.createElement('a');
      heartLink.href = '#';
      heartLink.setAttribute('data-product-id', p.id || '');
      heartLink.className = 'phantom-wishlist-trigger';
      const heartImg = document.createElement('img');
      var wishlist = getWishlist();
      heartImg.src = resolveUrl(wishlist.indexOf(p.id) !== -1 ? 'assets/images/feature-heart-filled.png' : 'assets/images/feature-heart.png');
      heartImg.alt = 'wishlist';
      heartImg.className = 'img-fluid';
      heartLink.appendChild(heartImg);
      heartLi.appendChild(heartLink);
      ul.appendChild(heartLi);
    }
    // Quick view icon — only when setting enabled
    if (quickViewEnabled) {
      const eyeLi = document.createElement('li');
      eyeLi.className = 'icon';
      const eyeLink = document.createElement('a');
      eyeLink.href = '#';
      eyeLink.className = 'phantom-quickview-trigger';
      eyeLink.setAttribute('data-product-id', p.id || '');
      const eyeImg = document.createElement('img');
      eyeImg.src = resolveUrl('assets/images/feature-eye.png');
      eyeImg.alt = 'quickview';
      eyeImg.className = 'img-fluid';
      eyeLink.appendChild(eyeImg);
      eyeLi.appendChild(eyeLink);
      ul.appendChild(eyeLi);
    }
    imgBox.appendChild(ul);
    sellerBox.appendChild(imgBox);
    const content = document.createElement('div');
    content.className = 'seller_box_content';
    const textWrapper = document.createElement('div');
    textWrapper.className = 'text_wrapper position-relative';
    const ratingVal = Math.round(parseFloat(p.rating) || 0);
    const rating = document.createElement('div');
    rating.className = 'rating d-flex align-items-center justify-content-center';
    for (let s = 0; s < 5; s++) {
      const star = document.createElement('i');
      star.className = s < ratingVal ? 'fa-solid fa-star' : 'fa-regular fa-star';
      star.style.color = s < ratingVal ? '#fcd668' : '#ccc';
      rating.appendChild(star);
    }
    const ratingSpan = document.createElement('span');
    ratingSpan.className = 'd-inline-block';
    ratingSpan.textContent = '(' + (p.rating || '0') + '/5)';
    rating.appendChild(ratingSpan);
    textWrapper.appendChild(rating);
    const h6 = document.createElement('h6');
    h6.className = 'heading6 archivo-font';
    const nameLink = document.createElement('a');
    nameLink.href = detailUrl;
    nameLink.textContent = p.name || '';
    h6.appendChild(nameLink);
    textWrapper.appendChild(h6);
    const priceDiv = document.createElement('div');
    priceDiv.className = 'objct-price';
    if (isSale) {
      const saleSpan = document.createElement('span');
      saleSpan.className = 'd-inline-block';
      saleSpan.textContent = '$' + salePrice;
      priceDiv.appendChild(saleSpan);
      priceDiv.appendChild(document.createTextNode(' '));
      const delSpan = document.createElement('span');
      delSpan.className = 'd-inline-block';
      const del = document.createElement('del');
      del.textContent = '$' + regPrice;
      delSpan.appendChild(del);
      priceDiv.appendChild(delSpan);
    } else {
      priceDiv.textContent = stripHtml(priceHtml);
    }
    textWrapper.appendChild(priceDiv);
    content.appendChild(textWrapper);
    sellerBox.appendChild(content);
    outer.appendChild(sellerBox);
    return outer;
  }

  function injectProducts(products, settings, allPageData) {
    if (!products) return;
    const showSaleBadge = settings ? !!+settings.card_sale_badge : true;
    const saleBadgeText = (settings && settings.card_sale_badge_text) || 'Sale!';
    document.querySelectorAll('[data-phantom-products]').forEach(function (container) {
      const count = parseInt(container.getAttribute('data-phantom-products'), 10) || products.length;
      if (products.length < count) {
        // Fetch more products from API
        fetchJSON('/products?per_page=' + count + '&page=1').then(function(data) {
          if (!data || !data.products) return;
          container.innerHTML = '';
          data.products.slice(0, count).forEach(function(p) {
            container.appendChild(buildProductCard(p, showSaleBadge, saleBadgeText, settings));
          });
        }).catch(function (err) {
          console.error('[PhantomCore] Product fetch failed:', err);
        });
        return;
      }
      container.innerHTML = '';
      products.slice(0, count).forEach(function(p) {
        container.appendChild(buildProductCard(p, showSaleBadge, saleBadgeText, settings));
      });
    });
  }

  // ─── SINGLE PRODUCT ──────────────────────────────────────

  function renderProduct(p) {
    if (!p || p.code) return;
    const el = document.querySelector('[data-phantom-product]');
    if (!el) el = document.body;

    document.title = (p.name || 'Product') + ' | Claudia';

    // Name
    const nameEl = el.querySelector('.heading4.archivo-font');
    if (nameEl) nameEl.textContent = p.name || '';

    // Price
    const priceEl = el.querySelector('.types_content .price');
    if (priceEl) {
      if (p.on_sale) {
        priceEl.innerHTML = '<ins>$' + (p.sale_price || p.price) + '</ins> <del>$' + (p.regular_price || p.price) + '</del>';
      } else {
      priceEl.innerHTML = p.price_html || '$' + (p.price || 0);
      }
    }

    // Description
    const descEl = el.querySelector('.types_content p.text-size-16');
    if (descEl) {
      descEl.textContent = p.short_description ? p.short_description.replace(/<[^>]+>/g, '') : (p.description ? p.description.replace(/<[^>]+>/g, '') : '');
    }

    // Main image (first tab pane)
    const mainImg = el.querySelector('#myTabContent .tab-pane.active.show figure.auction-img img, #myTabContent .tab-pane:first-child figure.auction-img img');
    if (mainImg) mainImg.src = p.image || '';

    // Gallery thumbnails
    const thumbs = el.querySelectorAll('#myTab ul.nav-tabs li.nav-item a.nav-link figure.auction-img img');
    const gallery = p.gallery || [];
    if (thumbs.length && p.image) {
      thumbs[0].src = p.image;
      for (let gi = 1; gi < thumbs.length && gi <= gallery.length; gi++) {
        thumbs[gi].src = gallery[gi - 1] || p.image;
      }
    }
    // Also update tab pane images for gallery
    const panes = el.querySelectorAll('#myTabContent .tab-pane figure.auction-img img');
    if (panes.length && p.image) {
      panes[0].src = p.image;
      for (let pi = 1; pi < panes.length && pi <= gallery.length; pi++) {
        panes[pi].src = gallery[pi - 1] || p.image;
      }
    }

    // Stock status
    const stockEl = el.querySelector('.in-stock');
    if (stockEl) stockEl.textContent = p.in_stock ? 'In stock' : 'Out of stock';

    // SKU, Categories, Tags
    const skuEl = el.querySelector('.guranted-safe-checkout .safe-types div:nth-child(1) .d-inline-block.font-weight-600');
    if (skuEl) skuEl.textContent = p.sku || 'N/A';

    const catEl = el.querySelector('.guranted-safe-checkout .safe-types div:nth-child(2) .d-inline-block.font-weight-600');
    if (catEl) catEl.textContent = (p.categories || []).join(', ') || 'N/A';

    // Add to cart button
    const atcBtn = el.querySelector('.quatity_button_wrapper a.primary_btn');
    if (atcBtn) {
      atcBtn.setAttribute('data-product_id', p.id || '');
      atcBtn.setAttribute('data-product_sku', p.sku || '');
    }

    // Set product ID on container for wishlist syncing
    el.setAttribute('data-product-id', p.id || '');

    // Sync wishlist icon on product detail page
    if (p.id) {
      var wl = getWishlist();
      var detailHeart = el.querySelector('.wishlist i');
      if (detailHeart) {
        detailHeart.className = wl.indexOf(p.id) !== -1 ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
      }
    }

    // Variable product support: render attribute dropdowns
    if (p.type === 'variable' && p.variation_attributes && p.variation_attributes.length) {
      const typesContent = priceEl ? priceEl.closest('.types_content') : null;
      if (typesContent) {
        const staticColors = typesContent.querySelector('.colors');
        const staticSizes = typesContent.querySelector('.sizes');
        if (staticColors) staticColors.style.display = 'none';
        if (staticSizes) staticSizes.style.display = 'none';

        let varContainer = typesContent.querySelector('.phantom-variations');
        if (!varContainer) {
          varContainer = document.createElement('div');
          varContainer.className = 'phantom-variations';
          const qtyWrapper = typesContent.querySelector('.quatity_button_wrapper');
          if (qtyWrapper) {
            typesContent.insertBefore(varContainer, qtyWrapper);
          } else {
            typesContent.appendChild(varContainer);
          }
        }
        varContainer.innerHTML = '';

        var selections = {};
        p.variation_attributes.forEach(function(attr) {
          var group = document.createElement('div');
          group.className = 'phantom-var-group';
          group.style.cssText = 'margin-bottom:12px;';
          var label = document.createElement('label');
          label.className = 'heading';
          label.style.cssText = 'display:block;font-weight:600;margin-bottom:4px;';
          label.textContent = attr.name + ':';
          group.appendChild(label);
          var select = document.createElement('select');
          select.className = 'phantom-var-select';
          select.setAttribute('data-taxonomy', attr.taxonomy);
          select.style.cssText = 'width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;';
          var defaultOpt = document.createElement('option');
          defaultOpt.value = '';
          defaultOpt.textContent = 'Choose ' + attr.name;
          select.appendChild(defaultOpt);
          attr.options.forEach(function(opt) {
            var option = document.createElement('option');
            option.value = opt.slug;
            option.textContent = opt.name;
            select.appendChild(option);
          });
          select.addEventListener('change', function() {
            selections[attr.taxonomy] = this.value || '';
            updateVariation();
          });
          group.appendChild(select);
          varContainer.appendChild(group);
        });

        function updateVariation() {
          var matched = null;
          (p.variations || []).forEach(function(v) {
            var match = true;
            var vAttrs = v.attributes || {};
            for (var key in selections) {
              var val = 'attribute_' + key;
              if (vAttrs[val] !== undefined && vAttrs[val] !== '' && vAttrs[val] !== selections[key]) {
                match = false;
                break;
              }
            }
            if (match) matched = v;
          });
              if (matched) {
            if (priceEl) {
              if (matched.sale_price) {
                priceEl.textContent = '$' + matched.sale_price;
                var strike = document.createElement('span');
                strike.className = 'd-inline-block strike';
                strike.textContent = '$' + matched.regular_price;
                priceEl.appendChild(strike);
              } else {
                priceEl.textContent = '$' + matched.price;
              }
            }
            if (matched.image && mainImg) mainImg.src = matched.image;
            if (stockEl) stockEl.textContent = matched.in_stock ? 'In stock' : 'Out of stock';
            if (skuEl) skuEl.textContent = matched.sku || 'N/A';
            if (atcBtn) {
              atcBtn.setAttribute('data-variation-id', matched.id);
              for (var key in selections) {
                if (selections[key]) atcBtn.setAttribute('data-attribute-' + key, selections[key]);
              }
              atcBtn.classList.remove('disabled');
            }
          } else {
            if (atcBtn) {
              atcBtn.removeAttribute('data-variation-id');
              atcBtn.classList.add('disabled');
            }
          }
        }

        if (atcBtn) atcBtn.classList.add('disabled');
      }
    }

    // Rating
    const ratingEl = el.querySelector('.types_content .rating span.d-inline-block');
    if (ratingEl) ratingEl.textContent = '(' + (p.rating || '0') + '/5)';

    // Product video
    const vidThumb = el.querySelector('.nav-video-thumb');
    const vidContainer = el.querySelector('#video-tab-pane .product-video-container');
    if (vidThumb && vidContainer && p.video_url) {
      vidThumb.style.display = '';
      const url = p.video_url;
      if (url.indexOf('youtube.com/watch') !== -1 || url.indexOf('youtu.be') !== -1) {
        const vid = url.match(/(?:v=|\/)([\w-]{11})/);
        if (vid) {
          const iframe = document.createElement('iframe');
          iframe.width = '100%';
          iframe.height = '450';
          iframe.src = 'https://www.youtube.com/embed/' + encodeURIComponent(vid[1]);
          iframe.setAttribute('frameborder', '0');
          iframe.allowFullscreen = true;
          vidContainer.innerHTML = '';
          vidContainer.appendChild(iframe);
        }
      } else if (url.indexOf('vimeo.com') !== -1) {
        const vim = url.match(/(\d+)/);
        if (vim) {
          const iframe = document.createElement('iframe');
          iframe.width = '100%';
          iframe.height = '450';
          iframe.src = 'https://player.vimeo.com/video/' + encodeURIComponent(vim[1]);
          iframe.setAttribute('frameborder', '0');
          iframe.allowFullscreen = true;
          vidContainer.innerHTML = '';
          vidContainer.appendChild(iframe);
        }
      } else {
        const sanitized = url.replace(/[^a-zA-Z0-9\-._~:\/?#@!$&'()*+,;=]/g, '');
        if (!sanitized || sanitized.indexOf('http') !== 0) return;
        const video = document.createElement('video');
        video.width = '100%';
        video.height = '450';
        video.controls = true;
        const source = document.createElement('source');
        source.src = sanitized;
        source.type = 'video/mp4';
        video.appendChild(source);
        vidContainer.innerHTML = '';
        vidContainer.appendChild(video);
      }
    }

    // 360 product viewer
    const threeSixtyThumb = el.querySelector('.nav-360-thumb');
    const canvas = el.querySelector('#product-360-canvas');
    if (threeSixtyThumb && canvas && p.images_360 && p.images_360.length >= 3) {
      threeSixtyThumb.style.display = '';
      init360Viewer(canvas, p.images_360);
    }
  }

  function init360Viewer(canvas, images) {
    const ctx = canvas.getContext('2d');
    const loaded = [];
    let loadedCount = 0;
    let current = 0;
    let isDown = false;
    let startX = 0;

    function preload(idx) {
      if (loaded[idx]) return;
      const img = new Image();
      img.onload = function () {
        loaded[idx] = img;
        loadedCount++;
        if (loadedCount === 1) drawFrame(0);
      };
      img.src = images[idx];
    }

    function drawFrame(idx) {
      const img = loaded[idx];
      if (!img) return;
      current = idx;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
      const x = (canvas.width - img.width * scale) / 2;
      const y = (canvas.height - img.height * scale) / 2;
      ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
    }

    images.forEach(function (_, i) { preload(i); });

    canvas.addEventListener('mousedown', function (e) {
      isDown = true;
      startX = e.clientX;
    });
    canvas.addEventListener('mousemove', function (e) {
      if (!isDown || loadedCount < images.length) return;
      const dx = e.clientX - startX;
      if (Math.abs(dx) > 20) {
        const dir = dx > 0 ? 1 : -1;
        const next = (current + dir + images.length) % images.length;
        drawFrame(next);
        startX = e.clientX;
      }
    });
    canvas.addEventListener('mouseup', function () { isDown = false; });
    canvas.addEventListener('mouseleave', function () { isDown = false; });
  }

  function injectSingleProduct() {
    const hasProductEl = document.querySelector('[data-phantom-product]') !== null;
    const params = new URLSearchParams(window.location.search);
    const productId = params.get('product_id');
    const slugMatch = window.location.pathname.match(/\/product\/([^\/]+)/);
    const slug = slugMatch ? slugMatch[1] : null;
    if (!hasProductEl && !productId && !slug) return;

    if (slug) {
      fetchJSON('/products?per_page=100').then(function (resp) {
        const allProducts = Array.isArray(resp) ? resp : (resp.products || []);
        const p = allProducts.find(function (pr) { return pr.slug === slug; });
        if (p) renderProduct(p);
      }).catch(function (err) {
        console.error('[PhantomCore] Product slug lookup error:', err);
      });
    } else {
      let id = productId;
      if (!id) {
        const el = document.querySelector('[data-phantom-product]');
        id = el ? el.getAttribute('data-product-id') : null;
      }
      if (!id) return;
      fetchJSON('/products/' + id).then(function (resp) {
        const p = resp && resp.product ? resp.product : (Array.isArray(resp) ? resp[0] : resp);
        renderProduct(p);
      }).catch(function (err) {
        console.error('[PhantomCore] Product detail error:', err);
      });
    }
  }

  // ─── SHOP SORT / PAGINATION ──────────────────────────────

  function initShopControls() {
    var sortSelect = document.querySelector('.phantom-sort-select');
    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        var container = document.querySelector('.shop-products-con[data-phantom-products]');
        if (!container) return;
        var count = parseInt(container.getAttribute('data-phantom-products'), 10) || 12;
        var orderby = this.value || 'menu_order';
        var url = '/products?per_page=' + count + '&page=1&orderby=' + encodeURIComponent(orderby);
        delete cache[url];
        fetchJSON(url).then(function (data) {
          if (!data || !data.products) return;
          container.innerHTML = '';
          var showSaleBadge = !(+_settings.card_sale_badge === 0);
          var saleBadgeText = _settings.card_sale_badge_text || 'Sale!';
          data.products.forEach(function (p) {
            container.appendChild(buildProductCard(p, showSaleBadge, saleBadgeText, _settings));
          });
          renderPagination(data.totalPages || 1, 1, container);
          window.scrollTo({ top: container.offsetTop - 100, behavior: 'smooth' });
        }).catch(function (err) {
          console.error('[PhantomCore] Sort fetch failed:', err);
        });
      });
    }

    document.addEventListener('click', function (e) {
      var pageLink = e.target.closest('.phantom-pagination a[data-page]');
      if (!pageLink) return;
      e.preventDefault();
      var container = document.querySelector('.shop-products-con[data-phantom-products]');
      if (!container) return;
      var count = parseInt(container.getAttribute('data-phantom-products'), 10) || 12;
      var page = pageLink.getAttribute('data-page');
      if (page === 'prev') {
        var active = document.querySelector('.phantom-pagination .page-item.active');
        page = active ? parseInt(active.querySelector('a').getAttribute('data-page'), 10) - 1 : 1;
        if (page < 1) return;
      } else if (page === 'next') {
        var activePage = document.querySelector('.phantom-pagination .page-item.active');
        page = activePage ? parseInt(activePage.querySelector('a').getAttribute('data-page'), 10) + 1 : 1;
      } else {
        page = parseInt(page, 10);
      }
      var sortVal = document.querySelector('.phantom-sort-select');
      var orderby = sortVal ? sortVal.value : 'menu_order';
      var url = '/products?per_page=' + count + '&page=' + page + '&orderby=' + encodeURIComponent(orderby);
      delete cache[url];
      fetchJSON(url).then(function (data) {
        if (!data || !data.products) return;
        container.innerHTML = '';
        var showSaleBadge = !(+_settings.card_sale_badge === 0);
        var saleBadgeText = _settings.card_sale_badge_text || 'Sale!';
        data.products.forEach(function (p) {
          container.appendChild(buildProductCard(p, showSaleBadge, saleBadgeText, _settings));
        });
        renderPagination(data.totalPages || 1, page, container);
        window.scrollTo({ top: container.offsetTop - 100, behavior: 'smooth' });
      }).catch(function (err) {
        console.error('[PhantomCore] Pagination fetch failed:', err);
      });
    });
  }

  function renderPagination(totalPages, currentPage, container) {
    var pagination = document.querySelector('.phantom-pagination');
    if (!pagination) return;
    pagination.querySelectorAll('.phantom-page-num').forEach(function (li) { li.remove(); });
    var prevLink = pagination.querySelector('.phantom-page-prev');
    for (var pi = 1; pi <= totalPages; pi++) {
      var pageLi = document.createElement('li');
      pageLi.className = 'page-item' + (pi === currentPage ? ' active' : '') + ' phantom-page-num';
      var pageA = document.createElement('a');
      pageA.className = 'page-link';
      pageA.href = '#';
      pageA.setAttribute('data-page', pi);
      pageA.textContent = pi;
      pageLi.appendChild(pageA);
      if (prevLink && prevLink.nextSibling) {
        pagination.insertBefore(pageLi, prevLink.nextSibling);
      } else {
        pagination.insertBefore(pageLi, pagination.querySelector('.phantom-page-next'));
      }
    }
  }

  // ─── CART ────────────────────────────────────────────────

  function injectCart() {
    // Check minicart setting
    if (!_settings || !_settings.hasOwnProperty('shop_minicart_enable') || !+_settings.shop_minicart_enable) {
      var cartEl = document.querySelector('.shopping-cart');
      if (cartEl) cartEl.style.display = 'none';
      return;
    }
    var cartEl = document.querySelector('.shopping-cart');
    if (cartEl) cartEl.style.display = '';
    const cartInfo = document.querySelector('.shopping-cart .shopping-cart-info');
    if (!cartInfo) return;

    fetchJSON('/cart').then(function (data) {
      const items = data.items || [];
      cartInfo.innerHTML = '';

      items.forEach(function (item) {
        const div = document.createElement('div');
        div.className = 'product d-sm-flex d-block align-items-center';

        const prodDetails = document.createElement('div');
        prodDetails.className = 'product-details';

        const imgBox = document.createElement('div');
        imgBox.className = 'product-image box1';
        const fig = document.createElement('figure');
        fig.className = 'mb-0';
        const img = document.createElement('img');
        img.src = item.image || '';
        img.alt = item.name || '';
        img.className = 'img-fluid';
        fig.appendChild(img);
        imgBox.appendChild(fig);
        prodDetails.appendChild(imgBox);

        const prodContent = document.createElement('div');
        prodContent.className = 'product-content';
        const titleSpan = document.createElement('span');
        titleSpan.className = 'product-title';
        const titleLink = document.createElement('a');
        titleLink.href = '/product/?product_id=' + (item.id || '');
        titleLink.textContent = item.name || '';
        titleSpan.appendChild(titleLink);
        prodContent.appendChild(titleSpan);
        prodDetails.appendChild(prodContent);
        div.appendChild(prodDetails);

        const priceDiv = document.createElement('div');
        priceDiv.className = 'product-price';
        const priceSpan = document.createElement('span');
        priceSpan.textContent = item.price || '';
        priceDiv.appendChild(priceSpan);
        div.appendChild(priceDiv);

        const qtyDiv = document.createElement('div');
        qtyDiv.className = 'product-quantity d-flex';
        const qtyDetails = document.createElement('div');
        qtyDetails.className = 'product-qty-details';
        const decBtn = document.createElement('button');
        decBtn.className = 'value-button decrease-button';
        decBtn.setAttribute('data-cart-key', item.key || '');
        decBtn.textContent = '-';
        qtyDetails.appendChild(decBtn);
        const numDiv = document.createElement('div');
        numDiv.className = 'number';
        numDiv.setAttribute('data-cart-key', item.key || '');
        numDiv.textContent = item.qty || 1;
        qtyDetails.appendChild(numDiv);
        const incBtn = document.createElement('button');
        incBtn.className = 'value-button increase-button';
        incBtn.setAttribute('data-cart-key', item.key || '');
        incBtn.textContent = '+';
        qtyDetails.appendChild(incBtn);
        qtyDiv.appendChild(qtyDetails);
        div.appendChild(qtyDiv);

        const linePriceDiv = document.createElement('div');
        linePriceDiv.className = 'product-line-price';
        const linePriceSpan = document.createElement('span');
        linePriceSpan.textContent = item.total || '';
        linePriceDiv.appendChild(linePriceSpan);
        div.appendChild(linePriceDiv);

        const removalDiv = document.createElement('div');
        removalDiv.className = 'product-removal';
        const rmBtn = document.createElement('button');
        rmBtn.className = 'remove-product';
        rmBtn.setAttribute('data-cart-key', item.key || '');
        const rmIcon = document.createElement('i');
        rmIcon.className = 'fas fa-times';
        rmBtn.appendChild(rmIcon);
        removalDiv.appendChild(rmBtn);
        div.appendChild(removalDiv);

        cartInfo.appendChild(div);
      });

      // Totals
      const subtotalEl = document.querySelector('.detail .list-unstyled li span.dollar');
      if (subtotalEl) subtotalEl.textContent = data.total || '$0';

      const totalEl = document.querySelector('.all-total .total span.dollar');
      if (totalEl) totalEl.textContent = data.total || '$0';

      // Cart count in header
      if (data.totalItems !== undefined) {
        updateCartCount(data.totalItems);
      }
    }).catch(function (err) {
      console.error('[PhantomCore] Cart fetch error:', err);
    });
  }

  function updateCartCount(count) {
    document.querySelectorAll('.last_list a.cart span, a.cart span').forEach(function (el) {
      el.textContent = count || 0;
    });
  }

  // ─── PHANTOM CART ─────────────────────────────────────

  function renderCartUI(data) {
    if (!data || !data.items) return;
    delete cache['/cart'];
    updateCartCount(data.totalItems);
    var cartInfo = document.querySelector('.shopping-cart .shopping-cart-info');
    if (!cartInfo) return;
    cartInfo.innerHTML = '';
    if (data.items.length === 0) {
      cartInfo.innerHTML = '<p>Your cart is empty.</p>';
      return;
    }
    data.items.forEach(function (item) {
      var div = document.createElement('div');
      div.className = 'shopping-cart-box';

      var mediaDiv = document.createElement('div');
      mediaDiv.className = 'media';

      var link = document.createElement('a');
      link.href = item.url;
      link.className = 'pull-left';
      var img = document.createElement('img');
      img.src = item.image;
      img.alt = item.name;
      img.className = 'img-fluid';
      link.appendChild(img);
      mediaDiv.appendChild(link);

      var bodyDiv = document.createElement('div');
      bodyDiv.className = 'media-body';
      var h6 = document.createElement('h6');
      var a = document.createElement('a');
      a.href = item.url;
      a.textContent = item.name;
      h6.appendChild(a);
      bodyDiv.appendChild(h6);

      var priceSpan = document.createElement('span');
      priceSpan.className = 'price';
      priceSpan.textContent = item.subtotal;
      bodyDiv.appendChild(priceSpan);

      var qtySpan = document.createElement('span');
      qtySpan.className = 'qty';
      qtySpan.textContent = 'Qty: ' + item.qty;
      bodyDiv.appendChild(qtySpan);

      mediaDiv.appendChild(bodyDiv);
      div.appendChild(mediaDiv);
      cartInfo.appendChild(div);
    });
  }

  function initWooCommerce() {
    document.addEventListener('click', function (e) {
      var btn, key;

      // Add to cart
      btn = e.target.closest('.add-to-cart-trigger, .quatity_button_wrapper a.primary_btn');
      if (btn) {
        e.preventDefault();
        var productId = btn.getAttribute('data-product_id');
        if (!productId) {
          var detailContainer = btn.closest('[data-phantom-product]');
          if (detailContainer) productId = detailContainer.getAttribute('data-product-id');
        }
        if (!productId) return;
        setButtonLoading(btn, true);
        var cartBody = { product_id: parseInt(productId, 10) || 0, quantity: 1 };
        var variationId = btn.getAttribute('data-variation-id');
        if (variationId) {
          cartBody.variation_id = parseInt(variationId, 10);
          cartBody.variation = {};
          var attrs = btn.attributes;
          for (var a = 0; a < attrs.length; a++) {
            var attrName = attrs[a].name;
            if (attrName.indexOf('data-attribute-') === 0) {
              cartBody.variation[attrName.replace('data-attribute-', '')] = attrs[a].value;
            }
          }
        }
        fetchJSON('/cart/add', { method: 'POST', body: cartBody }).then(function (data) {
          setButtonLoading(btn, false);
          showToast('Added to cart!', 'success');
          renderCartUI(data);
        }).catch(function (err) {
          setButtonLoading(btn, false);
          showToast('Failed to add to cart', 'error');
          console.error('[PhantomCore] Add to cart error:', err);
        });
        return;
      }

      // Remove from cart
      btn = e.target.closest('.remove-product');
      if (btn) {
        e.preventDefault();
        key = btn.getAttribute('data-cart-key');
        if (!key) return;
        setButtonLoading(btn, true);
        fetchJSON('/cart/remove', { method: 'POST', body: { key: key } }).then(function (data) {
          setButtonLoading(btn, false);
          showToast('Item removed', 'success');
          renderCartUI(data);
        }).catch(function (err) {
          setButtonLoading(btn, false);
          showToast('Failed to remove item', 'error');
          console.error('[PhantomCore] Remove from cart error:', err);
        });
        return;
      }

      // Coupon apply handler
      btn = e.target.closest('.apply-coupon-btn');
      if (btn) {
        e.preventDefault();
        var codeInput = btn.closest('.coupon-form') ? btn.closest('.coupon-form').querySelector('.coupon-input') : null;
        if (!codeInput) codeInput = document.querySelector('.coupon-input');
        var code = codeInput ? codeInput.value.trim() : '';
        if (!code) { showToast('Enter a coupon code', 'info'); return; }
        setButtonLoading(btn, true);
        fetchJSON('/cart/coupon', { method: 'POST', body: { code: code } }).then(function (data) {
          setButtonLoading(btn, false);
          showToast('Coupon applied!', 'success');
          renderCartUI(data);
        }).catch(function (err) {
          setButtonLoading(btn, false);
          showToast('Failed to apply coupon', 'error');
          console.error('[PhantomCore] Coupon error:', err);
        });
        return;
      }

      // Quantity minus (cart page)
      btn = e.target.closest('.decrease-button');
      if (btn && btn.closest('.shopping-cart')) {
        e.preventDefault();
        key = btn.getAttribute('data-cart-key');
        var numEl = document.querySelector('.number[data-cart-key="' + key + '"]');
        if (numEl && parseInt(numEl.textContent) > 1) {
          setButtonLoading(btn, true);
          var prevQty = parseInt(numEl.textContent);
          var newQty = prevQty - 1;
          numEl.textContent = newQty;
          fetchJSON('/cart/update', { method: 'POST', body: { key: key, quantity: newQty } }).then(function (data) {
            setButtonLoading(btn, false);
            showToast('Cart updated', 'success');
            renderCartUI(data);
          }).catch(function (err) {
            numEl.textContent = prevQty;
            setButtonLoading(btn, false);
            showToast('Failed to update quantity', 'error');
            console.error('[PhantomCore] Cart decrease error:', err);
          });
        }
        return;
      }

      // Quantity plus (cart page)
      btn = e.target.closest('.increase-button');
      if (btn && btn.closest('.shopping-cart')) {
        e.preventDefault();
        key = btn.getAttribute('data-cart-key');
        var numEl2 = document.querySelector('.number[data-cart-key="' + key + '"]');
        if (numEl2) {
          setButtonLoading(btn, true);
          var prevQty2 = parseInt(numEl2.textContent);
          var newQty2 = prevQty2 + 1;
          numEl2.textContent = newQty2;
          fetchJSON('/cart/update', { method: 'POST', body: { key: key, quantity: newQty2 } }).then(function (data) {
            setButtonLoading(btn, false);
            showToast('Cart updated', 'success');
            renderCartUI(data);
          }).catch(function (err) {
            numEl2.textContent = prevQty2;
            setButtonLoading(btn, false);
            showToast('Failed to update quantity', 'error');
            console.error('[PhantomCore] Cart increase error:', err);
          });
        }
        return;
      }
    });
  }

  // ─── CHECKOUT ────────────────────────────────────────────

  function injectCheckoutSummary() {
    if (!document.querySelector('.order-summary-con')) return;
    fetchJSON('/cart').then(function (data) {
      if (!data || !data.items) return;
      var subtotalEl = document.querySelector('.checkout-subtotal .dollar');
      var totalEl = document.querySelector('.checkout-total');
      var shippingCostEl = document.querySelector('.checkout-shipping-cost');
      var itemsContainer = document.querySelector('.checkout-items');
      if (subtotalEl) subtotalEl.textContent = data.subtotal || data.total || '$0.00';
      if (totalEl) totalEl.textContent = data.total || '$0.00';
      if (shippingCostEl) shippingCostEl.textContent = data.shipping_total || '$0.00';
      if (itemsContainer) {
        itemsContainer.innerHTML = '';
        data.items.forEach(function (item) {
          var div = document.createElement('div');
          div.className = 'product-details d-flex align-items-center w-100 mb-2';
          var imgDiv = document.createElement('div');
          imgDiv.className = 'product-image box1';
          var figure = document.createElement('figure');
          figure.className = 'mb-0';
          var img = document.createElement('img');
          img.src = item.image || './assets/images/cart-item.png';
          img.alt = item.name || '';
          img.className = 'img-fluid';
          figure.appendChild(img);
          imgDiv.appendChild(figure);
          var contentDiv = document.createElement('div');
          contentDiv.className = 'product-content';
          var nameSpan = document.createElement('span');
          nameSpan.className = 'product-title d-block';
          nameSpan.textContent = item.name || '';
          contentDiv.appendChild(nameSpan);
          var qtySpan = document.createElement('span');
          qtySpan.className = 'product-color text d-block';
          qtySpan.innerHTML = 'Qty: <span>' + (item.qty || 0) + '</span>';
          contentDiv.appendChild(qtySpan);
          var totalSpan = document.createElement('span');
          totalSpan.className = 'product-size text d-block mb-0 beige';
          totalSpan.textContent = item.total || '';
          contentDiv.appendChild(totalSpan);
          div.appendChild(imgDiv);
          div.appendChild(contentDiv);
          itemsContainer.appendChild(div);
        });
      }
    }).catch(function (err) {
      console.error('[PhantomCore] Checkout summary fetch failed:', err);
    });
  }

  function initCheckout() {
    const form = document.getElementById('contactpage');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(form);
      fd.append('action', 'woocommerce_ajax_checkout');
      const nonceEl = document.querySelector('meta[name="wc-nonce"]');
      if (nonceEl) fd.append('security', nonceEl.getAttribute('content'));
      const btn = form.querySelector('.submit_now');
      if (btn) btn.textContent = 'Processing...';

      fetch('/?wc-ajax=checkout', { method: 'POST', credentials: 'same-origin', body: fd }).then(function (r) {
        return r.json();
      }).then(function (data) {
        if (btn) btn.textContent = 'Next';
        if (data && data.result === 'success') {
          window.location.href = data.redirect || '/thank-you/';
        } else if (data && data.messages) {
          let errEl = document.querySelector('.checkout-errors');
          if (!errEl) {
            errEl = document.createElement('div');
            errEl.className = 'checkout-errors alert alert-danger mt-3';
            form.appendChild(errEl);
          }
          errEl.textContent = data.messages;
        }
      }).catch(function (err) {
        console.error('[PhantomCore] Checkout error:', err);
        if (btn) btn.textContent = 'Next';
      });
    });
  }

  function initShipping() {
    var shippingList = document.getElementById('shipping-methods-list');
    if (!shippingList) return;
    fetchJSON('/cart/shipping-methods', { method: 'POST' }).then(function (data) {
      if (!data || !data.methods || data.methods.length === 0) {
        shippingList.innerHTML = '<p>No shipping methods available.</p>';
        return;
      }
      shippingList.innerHTML = '';
      data.methods.forEach(function (method) {
        var label = document.createElement('label');
        label.className = 'shipping-method-label d-block';
        var radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'shipping_method';
        radio.value = method.id;
        radio.className = 'shipping-method-radio';
        if (method.id === data.selected) {
          radio.checked = true;
        }
        radio.addEventListener('change', function () {
          injectCheckoutSummary();
        });
        label.appendChild(radio);
        var cost = parseFloat(method.cost) + parseFloat(method.tax);
        label.appendChild(document.createTextNode(' ' + method.label + ' - $' + cost.toFixed(2)));
        shippingList.appendChild(label);
      });
    }).catch(function (err) {
      console.error('[PhantomCore] Shipping methods fetch failed:', err);
      shippingList.innerHTML = '<p>Could not load shipping options.</p>';
    });
  }

  function getSelectedShippingMethod() {
    var selected = document.querySelector('#shipping-methods-list input[name="shipping_method"]:checked');
    return selected ? selected.value : '';
  }

  // ─── POSTS / BLOG ────────────────────────────────────────

  function injectPosts(posts) {
    if (!posts || !posts.length) return;
    document.querySelectorAll('[data-phantom-posts]').forEach(function (container) {
      const count = parseInt(container.getAttribute('data-phantom-posts'), 10) || posts.length;
      const items = posts.slice(0, count);
      container.innerHTML = '';
      items.forEach(function (post) {
        const imgSrc = post.featured_image || resolveUrl('assets/images/single-blog-tab-img1.jpg');
        const dateStr = post.date ? new Date(post.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '';
        const div = document.createElement('div');
        div.className = 'single-blog-box';
        const figure = document.createElement('figure');
        figure.className = 'mb-0';
        const link1 = document.createElement('a');
        link1.href = '/post/?post_id=' + encodeURIComponent(post.id || post.slug || '');
        const img = document.createElement('img');
        img.src = imgSrc;
        img.alt = post.title || '';
        img.loading = 'lazy';
        img.className = 'img-fluid';
        link1.appendChild(img);
        figure.appendChild(link1);
        div.appendChild(figure);
        const details = document.createElement('div');
        details.className = 'single-blog-details';
        const ul = document.createElement('ul');
        ul.className = 'list-unstyled';
        const li1 = document.createElement('li');
        li1.className = 'position-relative';
        li1.innerHTML = '<i class="fas fa-user"></i> Posted by Admin';
        ul.appendChild(li1);
        if (dateStr) {
          const li2 = document.createElement('li');
          li2.className = 'position-relative';
          li2.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + escapeHtml(dateStr);
          ul.appendChild(li2);
        }
        details.appendChild(ul);
        const h4 = document.createElement('h4');
        const link2 = document.createElement('a');
        link2.href = '/post/?post_id=' + encodeURIComponent(post.id || post.slug || '');
        link2.textContent = post.title || '';
        h4.appendChild(link2);
        details.appendChild(h4);
        const p = document.createElement('p');
        p.textContent = post.excerpt ? post.excerpt.replace(/<[^>]+>/g, '') : '';
        details.appendChild(p);
        const btnDiv = document.createElement('div');
        btnDiv.className = 'generic-btn2';
        const link3 = document.createElement('a');
        link3.href = '/post/?post_id=' + encodeURIComponent(post.id || post.slug || '');
        link3.textContent = 'Read More';
        btnDiv.appendChild(link3);
        details.appendChild(btnDiv);
        div.appendChild(details);
        container.appendChild(div);
      });
    });
  }

  // ─── SINGLE BLOG POST ──────────────────────────────────────

  function injectSinglePost() {
    const params = new URLSearchParams(window.location.search);
    const postId = params.get('post_id');
    if (!postId) return;

    fetchJSON('/posts/' + encodeURIComponent(postId)).then(function (data) {
      const p = data && data.post ? data.post : (Array.isArray(data) ? data[0] : data);
      if (!p || p.code) return;

      const el = document.querySelector('[data-phantom-post]');
      if (!el) return;

      document.title = (p.title || '') + ' | Claudia Kids Collection';

      // Title
      const titleEl = el.querySelector('.content1 h4, .news-heading, .blog-detail-heading, h2, h1');
      if (titleEl) titleEl.textContent = p.title || '';

      // Date
      const dateSpans = el.querySelectorAll('.span-fa-outer-con span.text-size-14');
      if (dateSpans.length >= 2 && p.date) {
        dateSpans[1].textContent = new Date(p.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
      }

      // Content
      const contentEl = el.querySelector('.content1 p.text-size-16, .text-size-16, .news-detail-content p');
      if (contentEl && p.content) {
        contentEl.textContent = stripHtml(p.content);
      }

      // Featured image
      const imgEl = el.querySelector('.image1 img, .featured-image img, .blog-detail-image img');
      if (imgEl && p.featured_image) {
        imgEl.src = p.featured_image;
        imgEl.alt = p.title || '';
      }
    }).catch(function (err) {
      console.error('[PhantomCore] Single post error:', err);
    });
  }

  // ─── CATEGORIES ──────────────────────────────────────────

  function injectCategories(categories) {
    if (!categories || !categories.length) return;
    const catList = document.querySelector('#category1 ul.list-unstyled');
    if (!catList) return;
    catList.innerHTML = '';
    categories.forEach(function (cat) {
      const li = document.createElement('li');
      li.className = 'cat-item';
      const a = document.createElement('a');
      a.href = '/shop/?category=' + encodeURIComponent(cat.slug || '');
      a.className = 'd-block';
      a.textContent = (cat.name || '') + ' (' + (cat.count || 0) + ')';
      li.appendChild(a);
      catList.appendChild(li);
    });
  }

  function resolveUrl(val) {
    var base = (window.phantomData && window.phantomData.plugin_url) || '/wp-content/plugins/phantom-core/';
    if (!val || val.indexOf('http') === 0 || val.indexOf('data:') === 0 || val.indexOf('/wp-content/') === 0) return val;
    if (val.indexOf('assets/') === 0) return base + 'frontend/' + val;
    if (val.indexOf('/') === 0) return base + 'frontend/assets/images/' + val.replace(/^\//, '');
    return base + 'frontend/assets/images/' + val;
  }

  // ─── FOOTER ──────────────────────────────────────────────

  function injectFooter(settings) {
    if (!settings) return;

    // Logo
    const logoImg = document.querySelector('.footer-logo img');
    if (logoImg && settings.footer_logo) logoImg.src = resolveUrl(settings.footer_logo);

    // About text
    const aboutText = document.querySelector('.logo-content .text.text-size-14');
    if (aboutText && settings.footer_about_text) aboutText.innerHTML = escapeHtml(settings.footer_about_text).replace(/\n/g, '<br>');

    // Copyright
    const copyrightEl = document.querySelector('.copyright .content p');
    if (copyrightEl) {
      if (settings.footer_copyright) {
        copyrightEl.textContent = settings.footer_copyright.replace('%d', new Date().getFullYear());
      }
      copyrightEl.textContent = copyrightEl.textContent.replace(/2025/g, new Date().getFullYear().toString());
    }

    // Payment cards
    const paymentImg = document.querySelector('.copyright .content img');
    if (paymentImg && settings.footer_payment_cards) paymentImg.src = resolveUrl(settings.footer_payment_cards);

    // Contact info
    const phoneLink = document.querySelector('.icon ul.list-unstyled a[href^="tel:"]');
    if (phoneLink && settings.footer_phone) {
      phoneLink.href = 'tel:' + settings.footer_phone.replace(/[^0-9+]/g, '');
      phoneLink.textContent = settings.footer_phone;
    }
    const emailLink = document.querySelector('.icon ul.list-unstyled a[href^="mailto:"]');
    if (emailLink && settings.footer_email) {
      emailLink.href = 'mailto:' + settings.footer_email;
      emailLink.textContent = settings.footer_email;
    }
    const addressEl = document.querySelector('.icon ul.list-unstyled a.address, .icon ul.list-unstyled li:last-child a');
    if (addressEl && settings.footer_address) addressEl.innerHTML = escapeHtml(settings.footer_address).replace(/\n/g, '<br>');

    // Social links
    const socialUl = document.querySelector('.logo-content ul.social-icons');
    if (socialUl && settings.footer_social_links && settings.footer_social_links.length) {
      socialUl.innerHTML = '';
      settings.footer_social_links.forEach(function (s) {
        const iconMap = { facebook: 'fa-facebook-f', instagram: 'fa-instagram', youtube: 'fa-youtube', twitter: 'fa-x-twitter', pinterest: 'fa-pinterest' };
        const iconClass = iconMap[(s.platform || '').toLowerCase()] || 'fa-globe';
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = sanitizeUrl(s.url);
        const i = document.createElement('i');
        i.className = 'fa-brands ' + iconClass + ' social-networks';
        a.appendChild(i);
        li.appendChild(a);
        socialUl.appendChild(li);
      });
    }
  }

  // ─── BANNER ──────────────────────────────────────────────

  function injectBanner(settings) {
    if (!settings) return;
    const span = document.querySelector('.banner-span');
    if (span && settings.home_banner_heading) span.textContent = settings.home_banner_heading;
    const h1 = document.querySelector('.center-context h1.font-size92');
    if (h1 && settings.home_banner_title) h1.innerHTML = escapeHtml(settings.home_banner_title).replace(/\n/g, '<br>');
    const p = document.querySelector('.center-context p');
    if (p && settings.home_banner_description) p.innerHTML = escapeHtml(settings.home_banner_description).replace(/\n/g, '<br>');
    const cta = document.querySelector('.center-context a.secondary_btn');
    if (cta) {
      if (settings.home_banner_btn_text) cta.childNodes[0].textContent = settings.home_banner_btn_text;
      if (settings.home_banner_btn_url) cta.href = sanitizeUrl(settings.home_banner_btn_url);
    }
    const img1 = document.querySelector('.banner-img1');
    if (img1 && settings.home_banner_img1) img1.src = resolveUrl(settings.home_banner_img1);
    const img2 = document.querySelector('.banner-img2');
    if (img2 && settings.home_banner_img2) img2.src = resolveUrl(settings.home_banner_img2);
  }

  // ─── SEO ─────────────────────────────────────────────────

  function injectSEO(settings) {
    if (!settings) return;
    if (settings.seo_meta_description) {
      const meta = document.querySelector('meta[name="description"]');
      if (meta) meta.content = settings.seo_meta_description;
    }
    if (settings.seo_og_title) {
      const ogTitle = document.querySelector('meta[property="og:title"]');
      if (ogTitle) ogTitle.content = settings.seo_og_title;
    }
    if (settings.seo_og_description) {
      const ogDesc = document.querySelector('meta[property="og:description"]');
      if (ogDesc) ogDesc.content = settings.seo_og_description;
    }
    if (settings.seo_og_image) {
      const ogImg = document.querySelector('meta[property="og:image"]');
      if (ogImg) ogImg.content = settings.seo_og_image;
    }
  }

  // ─── WISHLIST (localStorage-based) ─────────────────────

  function getWishlist() {
    try { return JSON.parse(localStorage.getItem('phantom_wishlist') || '[]'); } catch(e) { return []; }
  }

  function toggleWishlist(productId) {
    var list = getWishlist();
    var idx = list.indexOf(productId);
    if (idx !== -1) { list.splice(idx, 1); } else { list.push(productId); }
    try { localStorage.setItem('phantom_wishlist', JSON.stringify(list)); } catch(e) {}
    return list;
  }

  function initWishlistEvents() {
    document.addEventListener('click', function(e) {
      var trigger = e.target.closest('.phantom-wishlist-trigger');
      if (trigger) {
        e.preventDefault();
        var pid = parseInt(trigger.getAttribute('data-product-id'), 10);
        if (!pid) return;
        var list = toggleWishlist(pid);
        var img = trigger.querySelector('img');
        if (img) {
          var isIn = list.indexOf(pid) !== -1;
          img.style.opacity = isIn ? '1' : '0.5';
          img.style.filter = isIn ? 'brightness(0) saturate(100%) invert(28%) sepia(91%) saturate(748%) hue-rotate(345deg)' : '';
        }
        showToast(isIn ? 'Added to wishlist' : 'Removed from wishlist', 'info');
        var detailHeart = document.querySelector('.wishlist i');
        if (detailHeart) detailHeart.className = isIn ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
        return;
      }
      // Product detail page wishlist
      var detailWishlist = e.target.closest('.wishlist');
      if (detailWishlist) {
        e.preventDefault();
        var productEl = detailWishlist.closest('[data-phantom-product]');
        var pid = productEl ? parseInt(productEl.getAttribute('data-product-id'), 10) : 0;
        if (!pid) {
          var match = location.search.match(/product_id=(\d+)/);
          if (match) pid = parseInt(match[1], 10);
        }
        if (!pid) return;
        var list2 = toggleWishlist(pid);
        var icon = detailWishlist.querySelector('i');
        if (icon) icon.className = list2.indexOf(pid) !== -1 ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
        showToast(list2.indexOf(pid) !== -1 ? 'Added to wishlist' : 'Removed from wishlist', 'info');
      }
    });
  }

  // ─── QUICK VIEW MODAL ─────────────────────────────────

  function showQuickView(productId) {
    var overlay = document.querySelector('.phantom-qv-overlay');
    if (!overlay) {
      var style = document.createElement('style');
      style.textContent = '.phantom-qv-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:99998;display:flex;align-items:center;justify-content:center;}.phantom-qv-modal{background:#fff;border-radius:12px;max-width:800px;width:90%;max-height:80vh;overflow-y:auto;padding:30px;position:relative;}.phantom-qv-close{position:absolute;top:10px;right:15px;font-size:28px;cursor:pointer;color:#999;border:none;background:none;}.phantom-qv-close:hover{color:#333;}.phantom-qv-body{display:flex;gap:24px;flex-wrap:wrap;}.phantom-qv-image{flex:1;min-width:200px;}.phantom-qv-image img{width:100%;border-radius:8px;}.phantom-qv-info{flex:1;min-width:200px;}.phantom-qv-info h2{margin:0 0 8px;}.phantom-qv-price{font-size:22px;color:#e74c3c;font-weight:700;margin-bottom:12px;}.phantom-qv-desc{color:#666;line-height:1.6;margin-bottom:16px;}.phantom-qv-atc{display:inline-block;background:#333;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;cursor:pointer;border:none;font-size:16px;}';
      document.head.appendChild(style);
      overlay = document.createElement('div');
      overlay.className = 'phantom-qv-overlay';
      overlay.style.display = 'none';
      overlay.innerHTML = '<div class="phantom-qv-modal"><button class="phantom-qv-close">&times;</button><div class="phantom-qv-body"><div class="phantom-qv-image"><img src="" alt=""></div><div class="phantom-qv-info"><h2></h2><div class="phantom-qv-price"></div><div class="phantom-qv-desc"></div><a href="#" class="phantom-qv-atc add-to-cart-trigger">Add to Cart</a></div></div></div>';
      document.body.appendChild(overlay);
      overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.style.display = 'none'; });
      overlay.querySelector('.phantom-qv-close').addEventListener('click', function() { overlay.style.display = 'none'; });
    }
    var modal = overlay.querySelector('.phantom-qv-modal');
    var imgEl = modal.querySelector('.phantom-qv-image img');
    var titleEl = modal.querySelector('.phantom-qv-info h2');
    var priceEl = modal.querySelector('.phantom-qv-price');
    var descEl = modal.querySelector('.phantom-qv-desc');
    var atcEl = modal.querySelector('.phantom-qv-atc');

    fetchJSON('/products/' + productId).then(function(p) {
      if (!p || p.code) { showToast('Product not found', 'error'); return; }
      imgEl.src = p.image || '';
      titleEl.textContent = p.name || '';
      priceEl.textContent = stripHtml(p.price_html) || '$' + (p.price || '0');
      descEl.textContent = p.short_description ? p.short_description.replace(/<[^>]+>/g, '') : (p.description ? p.description.replace(/<[^>]+>/g, '') : '');
      atcEl.setAttribute('data-product_id', p.id || '');
      atcEl.setAttribute('data-product_sku', p.sku || '');
      atcEl.href = '/product/?product_id=' + (p.id || '');
      overlay.style.display = 'flex';
    }).catch(function(err) {
      showToast('Failed to load product', 'error');
      console.error('[PhantomCore] Quick view error:', err);
    });
  }

  function initQuickViewEvents() {
    document.addEventListener('click', function(e) {
      var trigger = e.target.closest('.phantom-quickview-trigger');
      if (!trigger) return;
      e.preventDefault();
      var pid = parseInt(trigger.getAttribute('data-product-id'), 10);
      if (pid) showQuickView(pid);
    });
  }

  // ─── IMAGE ZOOM (product detail page) ─────────────────

  function initImageZoom() {
    var mainImg = document.querySelector('#myTabContent .tab-pane.active.show figure.auction-img img, #myTabContent .tab-pane:first-child figure.auction-img img');
    if (!mainImg || mainImg._zoomInitialized) return;
    mainImg._zoomInitialized = true;
    var wrapper = mainImg.parentElement;
    if (!wrapper) return;
    wrapper.style.overflow = 'hidden';
    wrapper.style.position = 'relative';
    mainImg.style.transition = 'transform 0.2s ease';
    mainImg.style.cursor = 'zoom-in';
    mainImg.addEventListener('mouseenter', function() {
      mainImg.style.transform = 'scale(1.8)';
    });
    mainImg.addEventListener('mousemove', function(e) {
      var rect = wrapper.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      mainImg.style.transformOrigin = x + '% ' + y + '%';
    });
    mainImg.addEventListener('mouseleave', function() {
      mainImg.style.transform = 'scale(1)';
      mainImg.style.transformOrigin = 'center center';
    });
  }

  // ─── PRELOADER ───────────────────────────────────────────

  function hidePreloader() {
    const mask = document.querySelector('.loader-mask');
    if (mask) {
      mask.style.opacity = '0';
      mask.style.pointerEvents = 'none';
      var isReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      setTimeout(function () { mask.style.display = 'none'; }, isReduced ? 0 : 500);
    }
  }

  // ─── TOAST NOTIFICATION SYSTEM ──────────────────────────

  function showToast(message, type) {
    type = type || 'success';
    var container = document.querySelector('.phantom-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'phantom-toast-container';
      container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px;';
      document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'phantom-toast phantom-toast-' + type;
    var bgColor = type === 'success' ? '#4caf50' : (type === 'error' ? '#f44336' : '#2196f3');
    var isReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    toast.style.cssText = 'background:' + bgColor + ';color:#fff;padding:12px 20px;border-radius:6px;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.15);opacity:' + (isReduced ? '1' : '0') + ';transform:' + (isReduced ? 'none' : 'translateX(100%)') + ';transition:all 0.3s ease;min-width:250px;';
    toast.textContent = message;
    container.appendChild(toast);
    if (!isReduced) {
      requestAnimationFrame(function () {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
      });
    }
    setTimeout(function () {
      if (isReduced) {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      } else {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
      }
    }, 3000);
  }

  function setButtonLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
      if (btn._origText === undefined) btn._origText = btn.textContent;
      btn.textContent = '...';
      btn.style.pointerEvents = 'none';
      btn.style.opacity = '0.7';
    } else {
      if (btn._origText !== undefined) btn.textContent = btn._origText;
      btn.style.pointerEvents = '';
      btn.style.opacity = '';
    }
  }

  // ─── AUTH HANDLERS ───────────────────────────────────────

  function initAuthForms() {
    var path = window.location.pathname.replace(/\/+$/, '') || '/';

    if (path === '/login' || path === '/my-account') {
      var loginForm = document.querySelector('form[action*="/login/"]');
      if (loginForm && !loginForm._phantomBound) {
        loginForm._phantomBound = true;
        loginForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var btn = loginForm.querySelector('button[type="submit"]');
          setButtonLoading(btn, true);
          var email = loginForm.querySelector('input[name="email"]');
          var pass = loginForm.querySelector('input[name="password"]');
          var remember = loginForm.querySelector('input[name="userRememberMe"]');
          fetchJSON('/auth/login', {
            method: 'POST',
            body: { email: email ? email.value : '', password: pass ? pass.value : '', remember: remember ? remember.checked : false }
          }).then(function (data) {
            setButtonLoading(btn, false);
            if (data && data.success) {
              showToast('Login successful!', 'success');
              window.phantomData.api_nonce = data.api_nonce;
              window.phantomData.is_logged_in = true;
              window.phantomData.user_name = data.user_name;
              setTimeout(function () { window.location.href = '/'; }, 800);
            } else {
              showToast(data && data.message ? data.message : 'Login failed.', 'error');
            }
          }).catch(function (err) {
            setButtonLoading(btn, false);
            showToast('Login error. Please try again.', 'error');
            console.error('[PhantomCore] Login error:', err);
          });
        });
      }
    }

    if (path === '/join-now' || path === '/register') {
      var regForm = document.querySelector('form[action*="/join-now/"]');
      if (regForm && !regForm._phantomBound) {
        regForm._phantomBound = true;
        regForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var btn = regForm.querySelector('button[type="submit"]');
          setButtonLoading(btn, true);
          var name = regForm.querySelector('input[name="text"]');
          var email = regForm.querySelector('input[name="email"]');
          var pass = regForm.querySelector('input[name="password"]');
          fetchJSON('/auth/register', {
            method: 'POST',
            body: { name: name ? name.value : '', email: email ? email.value : '', password: pass ? pass.value : '' }
          }).then(function (data) {
            setButtonLoading(btn, false);
            if (data && data.success) {
              showToast('Account created!', 'success');
              window.phantomData.api_nonce = data.api_nonce;
              window.phantomData.is_logged_in = true;
              window.phantomData.user_name = data.user_name;
              setTimeout(function () { window.location.href = '/'; }, 800);
            } else {
              showToast(data && data.message ? data.message : 'Registration failed.', 'error');
            }
          }).catch(function (err) {
            setButtonLoading(btn, false);
            showToast('Registration error. Please try again.', 'error');
            console.error('[PhantomCore] Register error:', err);
          });
        });
      }
    }

    // Handle reset URL clicks — use dynamic URL
    var resetLink = document.querySelector('[data-phantom-reset-url]');
    if (resetLink) {
      resetLink.href = (window.phantomData && window.phantomData.rest_url) ? window.phantomData.rest_url.replace(/\/wp-json\/?$/, '') + '/password-reset/' : '/password-reset/';
    }

    if (path === '/password-reset') {
      var resetForm = document.querySelector('form[action*="/password-reset/"]');
      if (resetForm && !resetForm._phantomBound) {
        resetForm._phantomBound = true;
        resetForm.addEventListener('submit', function (e) {
          e.preventDefault();
          var btn = resetForm.querySelector('button[type="submit"]');
          setButtonLoading(btn, true);
          var email = resetForm.querySelector('input[name="email"]');
          fetchJSON('/auth/password-reset', {
            method: 'POST',
            body: { email: email ? email.value : '' }
          }).then(function (data) {
            setButtonLoading(btn, false);
            showToast(data && data.message ? data.message : 'If that email is registered, a reset link has been sent.', 'success');
            resetForm.querySelector('input[name="email"]').value = '';
          }).catch(function (err) {
            setButtonLoading(btn, false);
            showToast('Error sending reset link. Please try again.', 'error');
            console.error('[PhantomCore] Password reset error:', err);
          });
        });
      }
    }
  }

  function initLogout() {
    document.addEventListener('click', function (e) {
      var logoutLink = e.target.closest('[data-phantom-logout]');
      if (!logoutLink) return;
      e.preventDefault();
      fetchJSON('/auth/logout', { method: 'POST' }).then(function (data) {
        window.phantomData.api_nonce = data && data.api_nonce ? data.api_nonce : '';
        window.phantomData.is_logged_in = false;
        window.phantomData.user_name = '';
        showToast('Logged out successfully.', 'success');
        setTimeout(function () { window.location.href = '/'; }, 500);
      }).catch(function (err) {
        console.error('[PhantomCore] Logout error:', err);
        window.location.href = '/';
      });
    });
  }

  function initMyAccount() {
    var path = window.location.pathname.replace(/\/+$/, '') || '/';
    if (path !== '/my-account') return;

    var content = document.getElementById('phantom-account-content');
    if (!content) return;

    var isLoggedIn = window.phantomData && window.phantomData.is_logged_in;
    var userName = window.phantomData && window.phantomData.user_name;

    if (!isLoggedIn) {
      content.innerHTML = '<div class="text-center py-4"><p>You are not logged in.</p><a href="/login/" class="btn btn-primary mt-3">Log In</a></div>';
      return;
    }

    fetchJSON('/user/orders').then(function (data) {
      var html = '<div class="phantom-account-welcome text-center mb-4"><h3>Welcome, ' + escapeHtml(userName || 'User') + '</h3>';
      html += '<a href="#" data-phantom-logout class="btn btn-outline-secondary btn-sm mt-2">Log Out</a></div>';

      if (data && data.orders && data.orders.length > 0) {
        html += '<h5 class="mb-3">Recent Orders</h5><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th>Items</th></tr></thead><tbody>';
        data.orders.forEach(function (o) {
          html += '<tr><td>#' + escapeHtml(String(o.number)) + '</td><td>' + escapeHtml(String(o.date)) + '</td><td>' + escapeHtml(String(o.status)) + '</td><td>' + o.total + '</td><td>' + o.items + '</td></tr>';
        });
        html += '</tbody></table></div>';
      } else {
        html += '<div class="text-center py-4"><p>No orders yet.</p><a href="/shop/" class="btn btn-primary">Start Shopping</a></div>';
      }

      content.innerHTML = html;
    }).catch(function () {
      content.innerHTML = '<div class="text-center py-4"><p>Could not load order history.</p></div>';
    });
  }

  // ─── INIT ────────────────────────────────────────────────

  function initBlogPagination() {
    document.addEventListener('click', function (e) {
      var pageLink = e.target.closest('.phantom-blog-pagination a[data-page]');
      if (!pageLink) return;
      e.preventDefault();
      var container = document.querySelector('[data-phantom-posts]');
      if (!container) return;
      var page = pageLink.getAttribute('data-page');
      if (page === 'prev') {
        var active = document.querySelector('.phantom-blog-pagination .page-item.active');
        page = active ? parseInt(active.querySelector('a').getAttribute('data-page'), 10) - 1 : 1;
        if (page < 1) return;
      } else if (page === 'next') {
        var activePage = document.querySelector('.phantom-blog-pagination .page-item.active');
        page = activePage ? parseInt(activePage.querySelector('a').getAttribute('data-page'), 10) + 1 : 1;
      } else {
        page = parseInt(page, 10);
      }
      fetchJSON('/posts?per_page=6&page=' + page).then(function (data) {
        if (!data || !data.posts) return;
        container.innerHTML = '';
        data.posts.forEach(function (p) {
          var div = document.createElement('div');
          div.className = 'col-lg-4 col-md-6';
          div.innerHTML = '<div class="blog-single position-relative"><div class="blog-img position-relative"><figure class="mb-0"><a href="/blog/' + encodeURIComponent(p.slug) + '"><img src="' + (p.featured_image || resolveUrl('assets/images/blog-single.png')) + '" alt="' + escapeHtml(p.title) + '" class="img-fluid"></a></figure></div><div class="blog-content"><ul class="list-unstyled d-flex blog_meta"><li>' + escapeHtml(p.date) + '</li></ul><h4><a href="/blog/' + encodeURIComponent(p.slug) + '">' + escapeHtml(p.title) + '</a></h4><p>' + escapeHtml(p.excerpt) + '</p><div class="generic-btn2"><a href="/blog/' + encodeURIComponent(p.slug) + '">Read More</a></div></div></div>';
          container.appendChild(div);
        });
      }).catch(function (err) {
        console.error('[PhantomCore] Blog pagination error:', err);
      });
    });
  }

  function init() {
    if (document._phantomInited) return;
    document._phantomInited = true;

    fetchJSON('/cart').then(function (data) {
      if (data.totalItems !== undefined) updateCartCount(data.totalItems);
    }).catch(function (err) {
      console.error('[PhantomCore] Cart count fetch failed:', err);
    });
    fetchJSON('/page-data').then(function (data) {
      if (data.settings) {
        _settings = data.settings;
        injectSettings(data.settings);
        injectBanner(data.settings);
        injectFooter(data.settings);
        injectSEO(data.settings);
      }
      if (data.menus) injectMenus(data.menus);
      if (data.products) injectProducts(data.products, data.settings);
      if (data.posts) injectPosts(data.posts);
      if (data.categories) injectCategories(data.categories);

      injectSinglePost();

      initWooCommerce();
      initWishlistEvents();
      initQuickViewEvents();
      injectSingleProduct();
      injectCart();
      initCheckout();
      initShipping();
      initShopControls();
      initBlogPagination();
      initAuthForms();
      initLogout();
      initMyAccount();
      hidePreloader();
      if (data.settings && +data.settings.shop_product_image_zoom) initImageZoom();
    }).catch(function (err) {
      console.error('[PhantomCore] Init error:', err);
      hidePreloader();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.PhantomCore = { fetchJSON: fetchJSON, apiBase: apiBase, cache: cache };

})();
