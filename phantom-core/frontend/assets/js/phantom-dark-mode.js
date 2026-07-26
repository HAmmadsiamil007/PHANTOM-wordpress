/**
 * Phantom Dark Mode - Toggle with cookie persistence
 */
(function() {
  'use strict';
  var STORAGE_KEY = 'phantom_theme';
  function getPreferredTheme() {
    var stored = localStorage.getItem(STORAGE_KEY);
    if (stored) return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  function setTheme(theme) {
    if (theme === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
    localStorage.setItem(STORAGE_KEY, theme);
  }
  function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme');
    setTheme(current === 'dark' ? 'light' : 'dark');
  }
  setTheme(getPreferredTheme());
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-phantom-dark-toggle]').forEach(function(btn) {
      btn.addEventListener('click', toggleTheme);
    });
  });
  window.PhantomDarkMode = { toggle: toggleTheme, set: setTheme, get: getPreferredTheme };
})();
