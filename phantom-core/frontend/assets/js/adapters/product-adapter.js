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
