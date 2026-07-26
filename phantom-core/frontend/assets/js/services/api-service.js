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
