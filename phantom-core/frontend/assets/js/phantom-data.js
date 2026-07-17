/**
 * Phantom Core Data Bridge v2.0
 * Fetches data from Phantom Core REST API and injects into DOM.
 * Built for Layout-02-Kids-Collection frontend.
 */
(function () {
  'use strict';

  var apiBase = '/index.php?rest_route=/phantom/v1';
  var cache = {};

  function fetchJSON(path, timeout) {
    if (cache[path]) return Promise.resolve(cache[path]);
    timeout = timeout || 10000;
    var controller = new AbortController();
    var timer = setTimeout(function () { controller.abort(); }, timeout);
    var qIdx = path.indexOf('?');
    var url;
    if (qIdx === -1) {
      url = apiBase + path;
    } else {
      url = apiBase + path.substring(0, qIdx) + '&' + path.substring(qIdx + 1);
    }
    return fetch(url, { signal: controller.signal }).then(function (r) {
      clearTimeout(timer);
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    }).then(function (data) {
      cache[path] = data;
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
      var key = el.getAttribute('data-phantom');
      if (settings[key] === undefined || settings[key] === null) return;
      var val = String(settings[key]);
      if (el.tagName === 'IMG' || el.tagName === 'SOURCE') {
        // Prepend assets/images/ for relative paths that don't have it
        if (val.indexOf('/') !== 0 && val.indexOf('http') !== 0 && val.indexOf('assets/') !== 0) {
          val = 'assets/images/' + val;
        }
        el.setAttribute('src', val);
      } else if (el.tagName === 'A' && el.hasAttribute('href')) {
        el.setAttribute('href', val);
      } else {
        el.innerHTML = val.replace(/\n/g, '<br>');
      }
    });

    // data-phantom-bg="key" — sets background-image
    document.querySelectorAll('[data-phantom-bg]').forEach(function (el) {
      var key = el.getAttribute('data-phantom-bg');
      if (settings[key]) el.style.backgroundImage = 'url(' + settings[key] + ')';
    });
  }

  // ─── MENUS ───────────────────────────────────────────────

  function escapeHtml(str) {
    var d = document.createElement('div');
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
    var frag = document.createDocumentFragment();
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var hasChildren = item.children && item.children.length > 0;
      var li = document.createElement('li');
      li.className = 'nav-item' + (hasChildren ? ' dropdown' : '');
      if (hasChildren) {
        var a = document.createElement('a');
        a.className = 'nav-link dropdown-toggle dropdown-color navbar-text-color';
        a.href = sanitizeUrl(item.url);
        a.setAttribute('role', 'button');
        a.setAttribute('data-toggle', 'dropdown');
        a.setAttribute('aria-haspopup', 'true');
        a.setAttribute('aria-expanded', 'false');
        a.textContent = item.title;
        li.appendChild(a);
        var div = document.createElement('div');
        div.className = 'dropdown-menu drop-down-content';
        var ul = document.createElement('ul');
        ul.className = 'list-unstyled drop-down-pages';
        for (var j = 0; j < item.children.length; j++) {
          var child = item.children[j];
          var childLi = document.createElement('li');
          childLi.className = 'nav-item';
          var childA = document.createElement('a');
          childA.className = 'dropdown-item nav-link';
          childA.href = sanitizeUrl(child.url);
          childA.textContent = child.title;
          childLi.appendChild(childA);
          ul.appendChild(childLi);
        }
        div.appendChild(ul);
        li.appendChild(div);
      } else {
        var a = document.createElement('a');
        a.className = 'nav-link';
        a.href = sanitizeUrl(item.url);
        a.textContent = item.title;
        li.appendChild(a);
      }
      frag.appendChild(li);
    }
    var wrapper = document.createElement('div');
    wrapper.appendChild(frag);
    return wrapper.innerHTML;
  }

  function injectMenus(menus) {
    if (!menus) return;
    document.querySelectorAll('[data-phantom-menu]').forEach(function (el) {
      var location = el.getAttribute('data-phantom-menu');
      var menu = menus[location];
      var items = (menu && menu.items) || [];
      if (!items.length) return;
      el.innerHTML = buildMenuHTML(items);
      var path = window.location.pathname;
      var origin = window.location.origin;
      el.querySelectorAll('a.nav-link').forEach(function (a) {
        var href = a.getAttribute('href');
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

  function buildProductCard(p, showSaleBadge, saleBadgeText) {
    var imgSrc = p.image || '';
    var priceHtml = p.price_html || '$' + (p.price || '0');
    var detailUrl = '/product/?product_id=' + (p.id || '');
    var isSale = p.on_sale;
    var isFeatured = p.is_featured;
    var salePrice = p.sale_price || '';
    var regPrice = p.regular_price || p.price || '';

    var outer = document.createElement('div');
    outer.className = 'col-xl-4 col-lg-6 col-md-6 col-sm-6 d-flex';
    var sellerBox = document.createElement('div');
    sellerBox.className = 'seller-box w-100';
    var imgBox = document.createElement('div');
    imgBox.className = 'seller_image_box position-relative';
    if (isSale && showSaleBadge) {
      var saleTag = document.createElement('span');
      saleTag.className = 'd-inline-block position-absolute sale-tag background-primary text-white';
      saleTag.textContent = saleBadgeText;
      imgBox.appendChild(saleTag);
    }
    if (isFeatured) {
      var featTag = document.createElement('span');
      featTag.className = 'd-inline-block position-absolute featured-tag';
      featTag.textContent = 'Featured';
      featTag.style.top = isSale ? '48px' : '20px';
      imgBox.appendChild(featTag);
    }
    var figure = document.createElement('figure');
    figure.className = 'mb-0';
    var figLink = document.createElement('a');
    figLink.href = detailUrl;
    var prodImg = document.createElement('img');
    prodImg.src = imgSrc;
    prodImg.alt = p.name || '';
    prodImg.className = 'img-fluid';
    prodImg.loading = 'lazy';
    figLink.appendChild(prodImg);
    figure.appendChild(figLink);
    imgBox.appendChild(figure);
    var ul = document.createElement('ul');
    ul.className = 'list-unstyled mb-0';
    var cartLi = document.createElement('li');
    cartLi.className = 'icon';
    var cartLink = document.createElement('a');
    cartLink.href = detailUrl;
    cartLink.className = 'add-to-cart-trigger';
    cartLink.setAttribute('data-product_id', p.id || '');
    cartLink.setAttribute('data-product_sku', p.sku || '');
    var cartImg = document.createElement('img');
    cartImg.src = resolveUrl('assets/images/feature-cart.png');
    cartImg.alt = 'cart';
    cartImg.className = 'img-fluid';
    cartLink.appendChild(cartImg);
    cartLi.appendChild(cartLink);
    ul.appendChild(cartLi);
    var heartLi = document.createElement('li');
    heartLi.className = 'icon';
    var heartLink = document.createElement('a');
    heartLink.href = detailUrl;
    var heartImg = document.createElement('img');
    heartImg.src = resolveUrl('assets/images/feature-heart.png');
    heartImg.alt = 'wishlist';
    heartImg.className = 'img-fluid';
    heartLink.appendChild(heartImg);
    heartLi.appendChild(heartLink);
    ul.appendChild(heartLi);
    var eyeLi = document.createElement('li');
    eyeLi.className = 'icon';
    var eyeLink = document.createElement('a');
    eyeLink.href = detailUrl;
    var eyeImg = document.createElement('img');
    eyeImg.src = resolveUrl('assets/images/feature-eye.png');
    eyeImg.alt = 'quickview';
    eyeImg.className = 'img-fluid';
    eyeLink.appendChild(eyeImg);
    eyeLi.appendChild(eyeLink);
    ul.appendChild(eyeLi);
    imgBox.appendChild(ul);
    sellerBox.appendChild(imgBox);
    var content = document.createElement('div');
    content.className = 'seller_box_content';
    var textWrapper = document.createElement('div');
    textWrapper.className = 'text_wrapper position-relative';
    var ratingVal = Math.round(parseFloat(p.rating) || 0);
    var rating = document.createElement('div');
    rating.className = 'rating d-flex align-items-center justify-content-center';
    for (var s = 0; s < 5; s++) {
      var star = document.createElement('i');
      star.className = s < ratingVal ? 'fa-solid fa-star' : 'fa-regular fa-star';
      star.style.color = s < ratingVal ? '#fcd668' : '#ccc';
      rating.appendChild(star);
    }
    var ratingSpan = document.createElement('span');
    ratingSpan.className = 'd-inline-block';
    ratingSpan.textContent = '(' + (p.rating || '0') + '/5)';
    rating.appendChild(ratingSpan);
    textWrapper.appendChild(rating);
    var h6 = document.createElement('h6');
    h6.className = 'heading6 archivo-font';
    var nameLink = document.createElement('a');
    nameLink.href = detailUrl;
    nameLink.textContent = p.name || '';
    h6.appendChild(nameLink);
    textWrapper.appendChild(h6);
    var priceDiv = document.createElement('div');
    priceDiv.className = 'objct-price';
    if (isSale) {
      var saleSpan = document.createElement('span');
      saleSpan.className = 'd-inline-block';
      saleSpan.textContent = '$' + salePrice;
      priceDiv.appendChild(saleSpan);
      priceDiv.appendChild(document.createTextNode(' '));
      var delSpan = document.createElement('span');
      delSpan.className = 'd-inline-block';
      var del = document.createElement('del');
      del.textContent = '$' + regPrice;
      delSpan.appendChild(del);
      priceDiv.appendChild(delSpan);
    } else {
      priceDiv.innerHTML = priceHtml;
    }
    textWrapper.appendChild(priceDiv);
    content.appendChild(textWrapper);
    sellerBox.appendChild(content);
    outer.appendChild(sellerBox);
    return outer;
  }

  function injectProducts(products, settings, allPageData) {
    if (!products) return;
    var showSaleBadge = settings ? !!+settings.card_sale_badge : true;
    var saleBadgeText = (settings && settings.card_sale_badge_text) || 'Sale!';
    document.querySelectorAll('[data-phantom-products]').forEach(function (container) {
      var count = parseInt(container.getAttribute('data-phantom-products'), 10) || products.length;
      if (products.length < count) {
        // Fetch more products from API
        fetchJSON('/products?per_page=' + count + '&page=1').then(function(data) {
          if (!data || !data.products) return;
          container.innerHTML = '';
          data.products.slice(0, count).forEach(function(p) {
            container.appendChild(buildProductCard(p, showSaleBadge, saleBadgeText));
          });
        });
        return;
      }
      container.innerHTML = '';
      products.slice(0, count).forEach(function(p) {
        container.appendChild(buildProductCard(p, showSaleBadge, saleBadgeText));
      });
    });
  }

  // ─── SINGLE PRODUCT ──────────────────────────────────────

  function renderProduct(p) {
    if (!p || p.code) return;
    var el = document.querySelector('[data-phantom-product]');
    if (!el) el = document.body;

    document.title = (p.name || 'Product') + ' | Claudia';

    // Name
    var nameEl = el.querySelector('.heading4.archivo-font');
    if (nameEl) nameEl.textContent = p.name || '';

    // Price
    var priceEl = el.querySelector('.types_content .price');
    if (priceEl) {
      if (p.on_sale) {
        priceEl.innerHTML = '$' + (p.sale_price || p.price) + ' <span class="d-inline-block strike">$' + (p.regular_price || p.price) + '</span>';
      } else {
        priceEl.innerHTML = p.price_html || '$' + (p.price || '0');
      }
    }

    // Description
    var descEl = el.querySelector('.types_content p.text-size-16');
    if (descEl) {
      descEl.textContent = p.short_description ? p.short_description.replace(/<[^>]+>/g, '') : (p.description ? p.description.replace(/<[^>]+>/g, '') : '');
    }

    // Main image (first tab pane)
    var mainImg = el.querySelector('#myTabContent .tab-pane.active.show figure.auction-img img, #myTabContent .tab-pane:first-child figure.auction-img img');
    if (mainImg) mainImg.src = p.image || '';

    // Gallery thumbnails
    var thumbs = el.querySelectorAll('#myTab ul.nav-tabs li.nav-item a.nav-link figure.auction-img img');
    var gallery = p.gallery || [];
    if (thumbs.length && p.image) {
      thumbs[0].src = p.image;
      for (var gi = 1; gi < thumbs.length && gi <= gallery.length; gi++) {
        thumbs[gi].src = gallery[gi - 1] || p.image;
      }
    }
    // Also update tab pane images for gallery
    var panes = el.querySelectorAll('#myTabContent .tab-pane figure.auction-img img');
    if (panes.length && p.image) {
      panes[0].src = p.image;
      for (var pi = 1; pi < panes.length && pi <= gallery.length; pi++) {
        panes[pi].src = gallery[pi - 1] || p.image;
      }
    }

    // Stock status
    var stockEl = el.querySelector('.in-stock');
    if (stockEl) stockEl.textContent = p.in_stock ? 'In stock' : 'Out of stock';

    // SKU, Categories, Tags
    var skuEl = el.querySelector('.guranted-safe-checkout .safe-types div:nth-child(1) .d-inline-block.font-weight-600');
    if (skuEl) skuEl.textContent = p.sku || 'N/A';

    var catEl = el.querySelector('.guranted-safe-checkout .safe-types div:nth-child(2) .d-inline-block.font-weight-600');
    if (catEl) catEl.textContent = (p.categories || []).join(', ') || 'N/A';

    // Add to cart button
    var atcBtn = el.querySelector('.quatity_button_wrapper a.primary_btn');
    if (atcBtn) {
      atcBtn.setAttribute('data-product_id', p.id || '');
      atcBtn.setAttribute('data-product_sku', p.sku || '');
    }

    // Rating
    var ratingEl = el.querySelector('.types_content .rating span.d-inline-block');
    if (ratingEl) ratingEl.textContent = '(' + (p.rating || '0') + '/5)';

    // Product video
    var vidThumb = el.querySelector('.nav-video-thumb');
    var vidContainer = el.querySelector('#video-tab-pane .product-video-container');
    if (vidThumb && vidContainer && p.video_url) {
      vidThumb.style.display = '';
      var html = '';
      var url = p.video_url;
      if (url.indexOf('youtube.com/watch') !== -1 || url.indexOf('youtu.be') !== -1) {
        var vid = url.match(/(?:v=|\/)([\w-]{11})/);
        if (vid) html = '<iframe width="100%" height="450" src="https://www.youtube.com/embed/' + vid[1] + '" frameborder="0" allowfullscreen></iframe>';
      } else if (url.indexOf('vimeo.com') !== -1) {
        var vim = url.match(/(\d+)/);
        if (vim) html = '<iframe width="100%" height="450" src="https://player.vimeo.com/video/' + vim[1] + '" frameborder="0" allowfullscreen></iframe>';
      } else {
        html = '<video width="100%" height="450" controls><source src="' + url + '" type="video/mp4"></video>';
      }
      vidContainer.innerHTML = html;
    }

    // 360 product viewer
    var threeSixtyThumb = el.querySelector('.nav-360-thumb');
    var canvas = el.querySelector('#product-360-canvas');
    if (threeSixtyThumb && canvas && p.images_360 && p.images_360.length >= 3) {
      threeSixtyThumb.style.display = '';
      init360Viewer(canvas, p.images_360);
    }
  }

  function init360Viewer(canvas, images) {
    var ctx = canvas.getContext('2d');
    var loaded = [];
    var loadedCount = 0;
    var current = 0;
    var isDown = false;
    var startX = 0;

    function preload(idx) {
      if (loaded[idx]) return;
      var img = new Image();
      img.onload = function () {
        loaded[idx] = img;
        loadedCount++;
        if (loadedCount === 1) drawFrame(0);
      };
      img.src = images[idx];
    }

    function drawFrame(idx) {
      var img = loaded[idx];
      if (!img) return;
      current = idx;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      var scale = Math.min(canvas.width / img.width, canvas.height / img.height);
      var x = (canvas.width - img.width * scale) / 2;
      var y = (canvas.height - img.height * scale) / 2;
      ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
    }

    images.forEach(function (_, i) { preload(i); });

    canvas.addEventListener('mousedown', function (e) {
      isDown = true;
      startX = e.clientX;
    });
    canvas.addEventListener('mousemove', function (e) {
      if (!isDown || loadedCount < images.length) return;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 20) {
        var dir = dx > 0 ? 1 : -1;
        var next = (current + dir + images.length) % images.length;
        drawFrame(next);
        startX = e.clientX;
      }
    });
    canvas.addEventListener('mouseup', function () { isDown = false; });
    canvas.addEventListener('mouseleave', function () { isDown = false; });
  }

  function injectSingleProduct() {
    var hasProductEl = document.querySelector('[data-phantom-product]') !== null;
    var params = new URLSearchParams(window.location.search);
    var productId = params.get('product_id');
    var slugMatch = window.location.pathname.match(/\/product\/([^\/]+)/);
    var slug = slugMatch ? slugMatch[1] : null;
    if (!hasProductEl && !productId && !slug) return;

    if (slug) {
      fetchJSON('/products?per_page=100').then(function (resp) {
        var allProducts = Array.isArray(resp) ? resp : (resp.products || []);
        var p = allProducts.find(function (pr) { return pr.slug === slug; });
        if (p) renderProduct(p);
      }).catch(function (err) {
        console.error('[PhantomCore] Product slug lookup error:', err);
      });
    } else {
      var id = productId;
      if (!id) {
        var el = document.querySelector('[data-phantom-product]');
        id = el ? el.getAttribute('data-product-id') : null;
      }
      if (!id) return;
      fetchJSON('/products/' + id).then(function (resp) {
        var p = resp && resp.product ? resp.product : (Array.isArray(resp) ? resp[0] : resp);
        renderProduct(p);
      }).catch(function (err) {
        console.error('[PhantomCore] Product detail error:', err);
      });
    }
  }

  // ─── CART ────────────────────────────────────────────────

  function injectCart() {
    var cartInfo = document.querySelector('.shopping-cart .shopping-cart-info');
    if (!cartInfo) return;

    fetchJSON('/cart').then(function (data) {
      var items = data.items || [];
      cartInfo.innerHTML = '';

      items.forEach(function (item) {
        var div = document.createElement('div');
        div.className = 'product d-sm-flex d-block align-items-center';

        var prodDetails = document.createElement('div');
        prodDetails.className = 'product-details';

        var imgBox = document.createElement('div');
        imgBox.className = 'product-image box1';
        var fig = document.createElement('figure');
        fig.className = 'mb-0';
        var img = document.createElement('img');
        img.src = item.image || '';
        img.alt = item.name || '';
        img.className = 'img-fluid';
        fig.appendChild(img);
        imgBox.appendChild(fig);
        prodDetails.appendChild(imgBox);

        var prodContent = document.createElement('div');
        prodContent.className = 'product-content';
        var titleSpan = document.createElement('span');
        titleSpan.className = 'product-title';
        var titleLink = document.createElement('a');
        titleLink.href = '/product/?product_id=' + (item.id || '');
        titleLink.textContent = item.name || '';
        titleSpan.appendChild(titleLink);
        prodContent.appendChild(titleSpan);
        prodDetails.appendChild(prodContent);
        div.appendChild(prodDetails);

        var priceDiv = document.createElement('div');
        priceDiv.className = 'product-price';
        var priceSpan = document.createElement('span');
        priceSpan.textContent = item.price || '';
        priceDiv.appendChild(priceSpan);
        div.appendChild(priceDiv);

        var qtyDiv = document.createElement('div');
        qtyDiv.className = 'product-quantity d-flex';
        var qtyDetails = document.createElement('div');
        qtyDetails.className = 'product-qty-details';
        var decBtn = document.createElement('button');
        decBtn.className = 'value-button decrease-button';
        decBtn.setAttribute('data-cart-key', item.key || '');
        decBtn.textContent = '-';
        qtyDetails.appendChild(decBtn);
        var numDiv = document.createElement('div');
        numDiv.className = 'number';
        numDiv.setAttribute('data-cart-key', item.key || '');
        numDiv.textContent = item.qty || 1;
        qtyDetails.appendChild(numDiv);
        var incBtn = document.createElement('button');
        incBtn.className = 'value-button increase-button';
        incBtn.setAttribute('data-cart-key', item.key || '');
        incBtn.textContent = '+';
        qtyDetails.appendChild(incBtn);
        qtyDiv.appendChild(qtyDetails);
        div.appendChild(qtyDiv);

        var linePriceDiv = document.createElement('div');
        linePriceDiv.className = 'product-line-price';
        var linePriceSpan = document.createElement('span');
        linePriceSpan.textContent = item.total || '';
        linePriceDiv.appendChild(linePriceSpan);
        div.appendChild(linePriceDiv);

        var removalDiv = document.createElement('div');
        removalDiv.className = 'product-removal';
        var rmBtn = document.createElement('button');
        rmBtn.className = 'remove-product';
        rmBtn.setAttribute('data-cart-key', item.key || '');
        var rmIcon = document.createElement('i');
        rmIcon.className = 'fas fa-times';
        rmBtn.appendChild(rmIcon);
        removalDiv.appendChild(rmBtn);
        div.appendChild(removalDiv);

        cartInfo.appendChild(div);
      });

      // Totals
      var subtotalEl = document.querySelector('.detail .list-unstyled li span.dollar');
      if (subtotalEl) subtotalEl.textContent = data.total || '$0';

      var totalEl = document.querySelector('.all-total .total span.dollar');
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

  // ─── WOOCOMMERCE AJAX ────────────────────────────────────

  function getStoreNonce() {
    var el = document.querySelector('meta[name="wc-nonce"]');
    return el ? el.getAttribute('content') : '';
  }

  function wcAjax(endpoint, formData) {
    var url = '/?wc-ajax=' + endpoint;
    var nonceEl = document.querySelector('meta[name="wc-nonce"]');
    if (nonceEl) formData.append('security', nonceEl.getAttribute('content'));
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function (r) { return r.json(); });
  }

  function storeApiUpdateItem(key, qty) {
    return fetch('/wp-json/wc/store/v1/cart/update-item', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Nonce': getStoreNonce()
      },
      body: JSON.stringify({ key: key, quantity: qty })
    }).then(function (r) { return r.json(); });
  }

  function onCartUpdate(data) {
    if (data && data.fragments) {
      Object.keys(data.fragments).forEach(function (key) {
        var target = document.querySelector(key);
        if (target) target.outerHTML = data.fragments[key];
      });
    }
    if (data && data.cart_hash !== undefined) {
      injectCart();
    }
  }

  function initWooCommerce() {
    document.addEventListener('click', function (e) {
      var btn, fd, key;

      // Add to cart
      btn = e.target.closest('.add-to-cart-trigger, .quatity_button_wrapper a.primary_btn');
      if (btn) {
        e.preventDefault();
        var productId = btn.getAttribute('data-product_id');
        if (!productId) {
          // Try to get from parent container
          var detailContainer = btn.closest('[data-phantom-product]');
          if (detailContainer) {
            productId = detailContainer.getAttribute('data-product-id');
          }
        }
        if (!productId) return;
        fd = new FormData();
        fd.append('product_id', productId);
        fd.append('product_sku', btn.getAttribute('data-product_sku') || '');
        fd.append('quantity', 1);
        wcAjax('add_to_cart', fd).then(onCartUpdate).catch(function (err) {
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
        fd = new FormData();
        fd.append('cart_item_key', key);
        wcAjax('remove_from_cart', fd).then(onCartUpdate).catch(function (err) {
          console.error('[PhantomCore] Remove from cart error:', err);
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
          numEl.textContent = parseInt(numEl.textContent) - 1;
          storeApiUpdateItem(key, parseInt(numEl.textContent)).then(function (data) {
            if (data && data.items !== undefined) injectCart();
          }).catch(function (err) {
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
          numEl2.textContent = parseInt(numEl2.textContent) + 1;
          storeApiUpdateItem(key, parseInt(numEl2.textContent)).then(function (data) {
            if (data && data.items !== undefined) injectCart();
          }).catch(function (err) {
            console.error('[PhantomCore] Cart increase error:', err);
          });
        }
        return;
      }
    });
  }

  // ─── CHECKOUT ────────────────────────────────────────────

  function initCheckout() {
    var form = document.getElementById('contactpage');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(form);
      fd.append('action', 'woocommerce_ajax_checkout');
      var btn = form.querySelector('.submit_now');
      if (btn) btn.textContent = 'Processing...';

      wcAjax('checkout', fd).then(function (data) {
        if (btn) btn.textContent = 'Next';
        if (data && data.result === 'success') {
          window.location.href = data.redirect || '/thank-you/';
        } else if (data && data.messages) {
          var errEl = document.querySelector('.checkout-errors');
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

  // ─── POSTS / BLOG ────────────────────────────────────────

  function injectPosts(posts) {
    if (!posts || !posts.length) return;
    document.querySelectorAll('[data-phantom-posts]').forEach(function (container) {
      var count = parseInt(container.getAttribute('data-phantom-posts'), 10) || posts.length;
      var items = posts.slice(0, count);
      container.innerHTML = '';
      items.forEach(function (post) {
        var imgSrc = post.featured_image || resolveUrl('assets/images/single-blog-tab-img1.jpg');
        var dateStr = post.date ? new Date(post.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '';
        var div = document.createElement('div');
        div.className = 'single-blog-box';
        var figure = document.createElement('figure');
        figure.className = 'mb-0';
        var link1 = document.createElement('a');
        link1.href = '/post/?post_id=' + encodeURIComponent(post.id || post.slug || '');
        var img = document.createElement('img');
        img.src = imgSrc;
        img.alt = post.title || '';
        img.loading = 'lazy';
        img.className = 'img-fluid';
        link1.appendChild(img);
        figure.appendChild(link1);
        div.appendChild(figure);
        var details = document.createElement('div');
        details.className = 'single-blog-details';
        var ul = document.createElement('ul');
        ul.className = 'list-unstyled';
        var li1 = document.createElement('li');
        li1.className = 'position-relative';
        li1.innerHTML = '<i class="fas fa-user"></i> Posted by Admin';
        ul.appendChild(li1);
        if (dateStr) {
          var li2 = document.createElement('li');
          li2.className = 'position-relative';
          li2.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + escapeHtml(dateStr);
          ul.appendChild(li2);
        }
        details.appendChild(ul);
        var h4 = document.createElement('h4');
        var link2 = document.createElement('a');
        link2.href = '/post/?post_id=' + encodeURIComponent(post.id || post.slug || '');
        link2.textContent = post.title || '';
        h4.appendChild(link2);
        details.appendChild(h4);
        var p = document.createElement('p');
        p.textContent = post.excerpt ? post.excerpt.replace(/<[^>]+>/g, '') : '';
        details.appendChild(p);
        var btnDiv = document.createElement('div');
        btnDiv.className = 'generic-btn2';
        var link3 = document.createElement('a');
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
    var params = new URLSearchParams(window.location.search);
    var postId = params.get('post_id');
    if (!postId) return;

    fetchJSON('/posts/' + encodeURIComponent(postId)).then(function (data) {
      var p = data && data.post ? data.post : (Array.isArray(data) ? data[0] : data);
      if (!p || p.code) return;

      var el = document.querySelector('[data-phantom-post]');
      if (!el) return;

      document.title = (p.title || '') + ' | Claudia Kids Collection';

      // Title
      var titleEl = el.querySelector('.content1 h4, .news-heading, .blog-detail-heading, h2, h1');
      if (titleEl) titleEl.textContent = p.title || '';

      // Date
      var dateSpans = el.querySelectorAll('.span-fa-outer-con span.text-size-14');
      if (dateSpans.length >= 2 && p.date) {
        dateSpans[1].textContent = new Date(p.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
      }

      // Content
      var contentEl = el.querySelector('.content1 p.text-size-16, .text-size-16, .news-detail-content p');
      if (contentEl && p.content) {
        contentEl.innerHTML = p.content.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
      }

      // Featured image
      var imgEl = el.querySelector('.image1 img, .featured-image img, .blog-detail-image img');
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
    var catList = document.querySelector('#category1 ul.list-unstyled');
    if (!catList) return;
    catList.innerHTML = '';
    categories.forEach(function (cat) {
      var li = document.createElement('li');
      li.className = 'cat-item';
      var a = document.createElement('a');
      a.href = '/shop/?category=' + encodeURIComponent(cat.slug || '');
      a.className = 'd-block';
      a.textContent = (cat.name || '') + ' (' + (cat.count || 0) + ')';
      li.appendChild(a);
      catList.appendChild(li);
    });
  }

  function resolveUrl(val) {
    if (!val || val.indexOf('http') === 0 || val.indexOf('data:') === 0 || val.indexOf('/wp-content/') === 0) return val;
    if (val.indexOf('assets/') === 0) return '/wp-content/plugins/phantom-core/frontend/' + val;
    if (val.indexOf('/') === 0) return '/wp-content/plugins/phantom-core/frontend/assets/images/' + val.replace(/^\//, '');
    return '/wp-content/plugins/phantom-core/frontend/assets/images/' + val;
  }

  // ─── FOOTER ──────────────────────────────────────────────

  function injectFooter(settings) {
    if (!settings) return;

    // Logo
    var logoImg = document.querySelector('.footer-logo img');
    if (logoImg && settings.footer_logo) logoImg.src = resolveUrl(settings.footer_logo);

    // About text
    var aboutText = document.querySelector('.logo-content .text.text-size-14');
    if (aboutText && settings.footer_about_text) aboutText.innerHTML = settings.footer_about_text.replace(/\n/g, '<br>');

    // Copyright
    var copyrightEl = document.querySelector('.copyright .content p');
    if (copyrightEl && settings.footer_copyright) {
      copyrightEl.textContent = settings.footer_copyright.replace('%d', new Date().getFullYear());
    }

    // Payment cards
    var paymentImg = document.querySelector('.copyright .content img');
    if (paymentImg && settings.footer_payment_cards) paymentImg.src = resolveUrl(settings.footer_payment_cards);

    // Contact info
    var phoneLink = document.querySelector('.icon ul.list-unstyled a[href^="tel:"]');
    if (phoneLink && settings.footer_phone) {
      phoneLink.href = 'tel:' + settings.footer_phone.replace(/[^0-9+]/g, '');
      phoneLink.textContent = settings.footer_phone;
    }
    var emailLink = document.querySelector('.icon ul.list-unstyled a[href^="mailto:"]');
    if (emailLink && settings.footer_email) {
      emailLink.href = 'mailto:' + settings.footer_email;
      emailLink.textContent = settings.footer_email;
    }
    var addressEl = document.querySelector('.icon ul.list-unstyled a.address, .icon ul.list-unstyled li:last-child a');
    if (addressEl && settings.footer_address) addressEl.innerHTML = settings.footer_address.replace(/\n/g, '<br>');

    // Social links
    var socialUl = document.querySelector('.logo-content ul.social-icons');
    if (socialUl && settings.footer_social_links && settings.footer_social_links.length) {
      socialUl.innerHTML = '';
      settings.footer_social_links.forEach(function (s) {
        var iconMap = { facebook: 'fa-facebook-f', instagram: 'fa-instagram', youtube: 'fa-youtube', twitter: 'fa-x-twitter', pinterest: 'fa-pinterest' };
        var iconClass = iconMap[(s.platform || '').toLowerCase()] || 'fa-globe';
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = sanitizeUrl(s.url);
        var i = document.createElement('i');
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
    var span = document.querySelector('.banner-span');
    if (span && settings.home_banner_heading) span.textContent = settings.home_banner_heading;
    var h1 = document.querySelector('.center-context h1.font-size92');
    if (h1 && settings.home_banner_title) h1.innerHTML = settings.home_banner_title.replace(/\n/g, '<br>');
    var p = document.querySelector('.center-context p');
    if (p && settings.home_banner_description) p.innerHTML = settings.home_banner_description.replace(/\n/g, '<br>');
    var cta = document.querySelector('.center-context a.secondary_btn');
    if (cta) {
      if (settings.home_banner_btn_text) cta.childNodes[0].textContent = settings.home_banner_btn_text;
      if (settings.home_banner_btn_url) cta.href = settings.home_banner_btn_url;
    }
    var img1 = document.querySelector('.banner-img1');
    if (img1 && settings.home_banner_img1) img1.src = resolveUrl(settings.home_banner_img1);
    var img2 = document.querySelector('.banner-img2');
    if (img2 && settings.home_banner_img2) img2.src = resolveUrl(settings.home_banner_img2);
  }

  // ─── SEO ─────────────────────────────────────────────────

  function injectSEO(settings) {
    if (!settings) return;
    if (settings.seo_meta_description) {
      var meta = document.querySelector('meta[name="description"]');
      if (meta) meta.content = settings.seo_meta_description;
    }
    if (settings.seo_og_title) {
      var ogTitle = document.querySelector('meta[property="og:title"]');
      if (ogTitle) ogTitle.content = settings.seo_og_title;
    }
    if (settings.seo_og_description) {
      var ogDesc = document.querySelector('meta[property="og:description"]');
      if (ogDesc) ogDesc.content = settings.seo_og_description;
    }
    if (settings.seo_og_image) {
      var ogImg = document.querySelector('meta[property="og:image"]');
      if (ogImg) ogImg.content = settings.seo_og_image;
    }
  }

  // ─── PRELOADER ───────────────────────────────────────────

  function hidePreloader() {
    var mask = document.querySelector('.loader-mask');
    if (mask) {
      mask.style.opacity = '0';
      mask.style.pointerEvents = 'none';
      setTimeout(function () { mask.style.display = 'none'; }, 500);
    }
  }

  // ─── INIT ────────────────────────────────────────────────

  function init() {
    fetchJSON('/cart').then(function (data) {
      if (data.totalItems !== undefined) updateCartCount(data.totalItems);
    }).catch(function (err) {
      console.error('[PhantomCore] Cart count fetch failed:', err);
    });
    fetchJSON('/page-data').then(function (data) {
      if (data.settings) {
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
      injectSingleProduct();
      injectCart();
      initCheckout();
      hidePreloader();
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
