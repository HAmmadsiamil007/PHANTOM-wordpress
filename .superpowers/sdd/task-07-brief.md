# T0.7 — Merge phantom-bridge.js into modular JS services

**Goal:** Extract phantom-bridge.js functionality into existing modular services, create event-services.js, update phantom-core.js + build.js, delete phantom-bridge.js/min.js.

## Context

phantom-bridge.js (186 lines) provides:
1. `phantomFetch()` retry fetch — belongs in api-service.js
2. `init()` + `_injectCssVars()` + `getCssVars()` — belongs in phantom-core.js
3. `setSetting()` + `saveChanges()` + `_getNonce()` — belongs in api-service.js
4. `highlightElement()` + `openEditor()` — belongs in phantom-core.js
5. `onSettingChange()` + `offSettingChange()` + `_emit()` — merge into w.PhantomEvents

Existing `services/auth-service.js` already embeds a `w.PhantomEvents` with `on/emit/off` — extract that into its own file.

Existing `services/api-service.js` has `get/post` methods. Already has nonce logic. Add bridge's persist methods.

## Files to Create

### `services/event-services.js` (NEW)
Extract the w.PhantomEvents from auth-service.js. Add bridge's `onSettingChange/offSettingChange` pattern:

```javascript
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
    }
  };
})(window);
```

## Files to Modify

### `services/api-service.js`
- Add `phantomFetch` internal helper
- Add `setSetting(key, value)` — PUT to `/settings/{key}`, emit event on success
- Add `saveChanges(changes)` — POST to `/settings`, emit events on success
- Add `getNonce()` — reads from PhantomData or fallback

READ the existing api-service.js first. Add these methods to the existing `w.PhantomServices.Api` object (DO NOT overwrite the whole file):

Add after `postContact`:
```javascript
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
```

### `services/auth-service.js`
READ existing auth-service.js first. REMOVE the `w.PhantomEvents` block at the end (lines 40-58). Keep only `w.PhantomServices.Auth`. The `w.PhantomEvents && w.PhantomEvents.emit(...)` calls should remain — they'll be satisfied by the new event-services.js.

New auth-service.js should be:
```javascript
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
```

### `frontend/assets/js/phantom-core.js`
READ the existing file first. The file currently has:
- `init()` that reads PhantomData
- Uses PhantomBridge directly
- Guard patterns like `w.PhantomInjector && ...`

Add after the existing code:
1. A backward-compatible `w.PhantomBridge` shim that delegates to services
2. CSS var injection logic (ported from bridge's `_injectCssVars`)

The shim should look like:
```javascript
// Backward-compatible PhantomBridge shim
(function() {
  if (w.PhantomBridge) return; // already defined elsewhere
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
```

### `build.js`
READ existing build.js. Add `'services/event-services.js'` after the existing services entry in the manifest array.

### Delete these files:
- `phantom-core/frontend/assets/js/phantom-bridge.js`
- `phantom-core/frontend/assets/js/phantom-bridge.min.js`

## Verification
1. Run `node build.js` — should succeed with no errors
2. Check all 4 service files exist
3. Run grep on any remaining references to old patterns

## Commits
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/frontend/assets/js/services/event-services.js phantom-core/frontend/assets/js/services/api-service.js phantom-core/frontend/assets/js/services/auth-service.js phantom-core/frontend/assets/js/phantom-core.js phantom-core/build.js
git rm phantom-core/frontend/assets/js/phantom-bridge.js phantom-core/frontend/assets/js/phantom-bridge.min.js
git commit -m "feat(phase0): merge phantom-bridge.js into modular JS services, create event-services.js"
```
