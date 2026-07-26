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
})(window);
