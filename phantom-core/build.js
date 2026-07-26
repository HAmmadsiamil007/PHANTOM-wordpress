/**
 * Phantom Core Build Script
 * Concatenates modular JS files into phantom-data.js for backward compatibility.
 *
 * Usage: node build.js
 */

const fs = require('fs');
const path = require('path');

const JS_DIR = path.join(__dirname, 'frontend', 'assets', 'js');

const files = [
  // Adapters
  'adapters/product-adapter.js',
  'adapters/category-adapter.js',
  'adapters/post-adapter.js',
  // Renderer
  'renderer/component-renderer.js',
  'renderer/product-card.js',
  'renderer/category-card.js',
  'renderer/blog-card.js',
  'renderer/navigation.js',
  'renderer/hero.js',
  // Services
  'services/api-service.js',
  'services/cart-service.js',
  'services/auth-service.js',
  'services/event-services.js',
  // Entry point
  'phantom-core.js',
];

let bundle = '';

files.forEach(function(relPath) {
  var fullPath = path.join(JS_DIR, relPath);
  if (fs.existsSync(fullPath)) {
    bundle += '/* ' + relPath + ' */\n';
    bundle += fs.readFileSync(fullPath, 'utf8');
    bundle += '\n\n';
  } else {
    console.warn('WARN: File not found:', relPath);
  }
});

// Write phantom-data.js (backward-compatible bundle)
var outputPath = path.join(JS_DIR, 'phantom-data.js');
fs.writeFileSync(outputPath, bundle, 'utf8');
console.log('OK: Wrote ' + outputPath + ' (' + (bundle.length / 1024).toFixed(1) + ' KB)');

// Also write phantom-core.min.js (just a copy for now, replace with terser in production)
var minPath = path.join(JS_DIR, 'phantom-core.min.js');
fs.writeFileSync(minPath, bundle, 'utf8');
console.log('OK: Wrote ' + minPath + ' (' + (bundle.length / 1024).toFixed(1) + ' KB)');
