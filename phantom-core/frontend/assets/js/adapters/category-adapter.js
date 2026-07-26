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
