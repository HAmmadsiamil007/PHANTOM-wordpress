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
          if (productId) this.add(productId);
        }
      }.bind(this));
    },

    add: function(productId, quantity, variationData) {
      quantity = quantity || 1;
      var body = { product_id: parseInt(productId), quantity: parseInt(quantity) };
      if (variationData) body.variation = variationData;

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
