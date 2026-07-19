/**
 * PhantomBridge.js v1.0
 * Frontend adapter layer for Phantom Core.
 * Reads window.PhantomData and provides a decoupled API.
 *
 * Usage:
 *   <script src="phantom-bridge.js"></script>
 *   <script>PhantomBridge.init();</script>
 */
(function (root) {
  'use strict';

  var bridge = {
    _data: {},
    _cssVarMap: {},
    _listeners: {},
    _styleEl: null,
    _initialized: false,
    _baseUrl: (function() {
      var d = root.PhantomData && root.PhantomData.rest_url ? root.PhantomData.rest_url.replace(/\/+$/, '') : null;
      if (d) return d;
      var w = root.wpApiSettings && root.wpApiSettings.root ? root.wpApiSettings.root.replace(/\/+$/, '') : null;
      return w ? w + '/phantom/v1' : '/index.php?rest_route=/phantom/v1';
    })(),

    init: function (opts) {
      opts = opts || {};
      var data = root.PhantomData || opts.data || {};
      this._cssVarMap = opts.cssVarMap || data._cssVarMap || {};

      this._data = {};
      for (var key in data) {
        if (data.hasOwnProperty(key) && key.indexOf('_') !== 0) {
          this._data[key] = data[key];
        }
      }

      this._injectCssVars();
      this._initEditor();
      this._initialized = true;
      return this;
    },

    _injectCssVars: function () {
      var cssVars = this._cssVarMap;
      var rootEl = document.documentElement;

      for (var key in cssVars) {
        if (!cssVars.hasOwnProperty(key)) continue;
        var val = this._data[key];
        if (val === undefined || val === null) continue;
        rootEl.style.setProperty(cssVars[key], String(val));
      }

      var existing = document.getElementById('phantom-bridge-style');
      if (existing) existing.remove();
    },

    getSetting: function (key) {
      if (!this._initialized) return undefined;
      return this._data[key];
    },

    setSetting: function (key, value) {
      if (!this._initialized) return Promise.reject(new Error('Bridge not initialized'));
      this._data[key] = value;

      var cssVar = this._cssVarMap[key];
      if (cssVar) {
        document.documentElement.style.setProperty(cssVar, String(value));
      }

      var self = this;
      var nonce = (window.PhantomData && window.PhantomData.nonce) || (document.querySelector('meta[name="wp-rest-nonce"]') || {}).content || '';
      var self2 = this;
      return fetch(this._baseUrl + '/settings/' + encodeURIComponent(key), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify({ value: value }),
        credentials: 'same-origin'
      }).then(function (r) {
        if (r.status === 401) {
          var freshNonce = (document.querySelector('meta[name="wp-rest-nonce"]') || {}).content || '';
          return fetch(self2._baseUrl + '/settings/' + encodeURIComponent(key), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': freshNonce },
            body: JSON.stringify({ value: value }),
            credentials: 'same-origin'
          });
        }
        return r;
      }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        self._emit(key, value);
        return r.json();
      });
    },

    onSettingChange: function (key, callback) {
      if (!this._listeners[key]) this._listeners[key] = [];
      this._listeners[key].push(callback);
      return this;
    },

    offSettingChange: function (key, callback) {
      if (!this._listeners[key]) return;
      this._listeners[key] = this._listeners[key].filter(function (fn) {
        return fn !== callback;
      });
      return this;
    },

    _emit: function (key, value) {
      var fns = this._listeners[key] || [];
      for (var i = 0; i < fns.length; i++) {
        try { fns[i](value, key); } catch (e) { console.warn('[PhantomBridge] listener error', e); }
      }
    },

    getCssVars: function () {
      if (!this._initialized) return {};
      var result = {};
      for (var key in this._cssVarMap) {
        if (!this._cssVarMap.hasOwnProperty(key)) continue;
        result[this._cssVarMap[key]] = this._data[key];
      }
      return result;
    },

    highlightElement: function (selector) {
      var el = document.querySelector(selector);
      if (!el) return;
      el.classList.add('phantom-highlight');
      setTimeout(function () { el.classList.remove('phantom-highlight'); }, 2000);
    },

    openEditor: function (key) {
      var customizerUrl = (root.wpAdminUrl || '/wp-admin/') + 'customize.php?autofocus[control]=phantom_' + encodeURIComponent(key);
      window.open(customizerUrl, '_blank');
    },

    saveChanges: function (changes) {
      if (!this._initialized) return Promise.reject(new Error('Bridge not initialized'));
      var self = this;
      for (var key in changes) {
        if (!changes.hasOwnProperty(key)) continue;
        self._data[key] = changes[key];
      }
      var nonce = (window.PhantomData && window.PhantomData.nonce) || (document.querySelector('meta[name="wp-rest-nonce"]') || {}).content || '';
      return fetch(this._baseUrl + '/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify({ settings: changes }),
        credentials: 'same-origin'
      }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      }).then(function (data) {
        Object.keys(changes).forEach(function (k) { self._emit(k, changes[k]); });
        return data;
      });
    },

    /* ── Frontend Editor (conditional on can_edit) ────────────────────── */
    _editMode: false,
    _toolbar: null,

    _initEditor: function () {
      if (!this._data.can_edit) return;
      document.body.classList.add('phantom-editor-enabled');
      this._createToolbar();
      var self = this;
      document.addEventListener('click', function (e) { self._onElementClick(e); });
    },

    _getNonce: function () {
      var meta = document.querySelector('meta[name="wp-rest-nonce"]');
      return meta ? meta.getAttribute('content') : '';
    },

    _saveSetting: function (key, value) {
      var url = this._baseUrl + '/settings/' + encodeURIComponent(key);
      var nonce = this._getNonce();
      var headers = { 'Content-Type': 'application/json' };
      if (nonce) headers['X-WP-Nonce'] = nonce;
      return fetch(url, {
        method: 'PUT',
        credentials: 'same-origin',
        headers: headers,
        body: JSON.stringify({ value: value })
      }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      });
    },

    _showToast: function (msg, type) {
      var t = document.createElement('div');
      t.className = 'phantom-editor-toast phantom-editor-toast-' + (type || 'success');
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(function () {
        t.style.opacity = '0';
        setTimeout(function () { t.remove(); }, 300);
      }, 2000);
    },

    _onEditableBlur: function (e) {
      var el = e.currentTarget;
      var key = el.getAttribute('data-phantom-key');
      if (!key) return;
      var val = el.tagName === 'A' ? el.getAttribute('href') : el.innerHTML.replace(/<br\s*\/?>/g, '\n');
      el.removeAttribute('contenteditable');
      el.classList.remove('phantom-editing');
      var self = this;
      this._saveSetting(key, val).then(function () {
        self._showToast('Saved: ' + key, 'success');
      }).catch(function () {
        self._showToast('Save failed: ' + key, 'error');
      });
    },

    _onEditableKeydown: function (e) {
      if (e.key === 'Escape') {
        e.preventDefault();
        e.target.blur();
      }
    },

    _activateEdit: function (el) {
      if (el.getAttribute('contenteditable') === 'true') return;
      el.setAttribute('contenteditable', 'true');
      el.classList.add('phantom-editing');
      el.focus();
      var self = this;
      el.addEventListener('blur', function (e) { self._onEditableBlur(e); }, { once: true });
      el.addEventListener('keydown', function (e) { self._onEditableKeydown(e); });
    },

    _onElementClick: function (e) {
      if (!this._editMode) return;
      var el = e.target.closest('[data-phantom-key]');
      if (!el) return;
      e.preventDefault();
      e.stopPropagation();
      this._activateEdit(el);
    },

    _toggleEditMode: function () {
      this._editMode = !this._editMode;
      var btn = this._toolbar.querySelector('.phantom-editor-toggle');
      if (this._editMode) {
        btn.textContent = 'Exit Edit Mode';
        btn.classList.add('active');
        document.body.classList.add('phantom-edit-mode');
      } else {
        btn.textContent = 'Edit Page';
        btn.classList.remove('active');
        document.body.classList.remove('phantom-edit-mode');
        document.querySelectorAll('[contenteditable="true"]').forEach(function (el) {
          el.removeAttribute('contenteditable');
          el.classList.remove('phantom-editing');
        });
      }
    },

    _createToolbar: function () {
      this._toolbar = document.createElement('div');
      this._toolbar.className = 'phantom-editor-toolbar';
      var container = document.createElement('div');
      container.className = 'phantom-editor-inner';

      var label = document.createElement('span');
      label.className = 'phantom-editor-label';
      label.textContent = 'Frontend Editor';
      container.appendChild(label);

      var btn = document.createElement('button');
      btn.className = 'phantom-editor-toggle';
      btn.textContent = 'Edit Page';
      btn.setAttribute('title', 'Toggle frontend edit mode');
      var self = this;
      btn.addEventListener('click', function () { self._toggleEditMode(); });
      container.appendChild(btn);

      var help = document.createElement('span');
      help.className = 'phantom-editor-help';
      help.textContent = 'Click any text to edit inline';
      container.appendChild(help);

      this._toolbar.appendChild(container);
      document.body.appendChild(this._toolbar);
    }
  };

  root.PhantomBridge = bridge;

})(window);
