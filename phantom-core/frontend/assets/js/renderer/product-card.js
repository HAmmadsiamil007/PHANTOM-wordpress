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
